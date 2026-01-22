<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Get product list with search, filter, and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->input('per_page', 20), 100);

        // Parse price_change - can be comma-separated string or array
        $priceChange = $request->input('price_change');
        if (is_string($priceChange) && str_contains($priceChange, ',')) {
            $priceChange = explode(',', $priceChange);
        }
        $changeDays = min($request->input('change_days', 7), 30);

        $query = Product::query()
            ->search($request->input('q'))
            ->brand($request->input('brand'))
            ->gender($request->input('gender'))
            ->priceChange($priceChange, $changeDays);

        // Apply sorting
        $sort = $request->input('sort');
        $query->when($sort, function ($query, $sort) {
            return match ($sort) {
                'price_asc' => $query->orderBy('current_price', 'asc'),
                'price_desc' => $query->orderBy('current_price', 'desc'),
                'name' => $query->orderBy('name', 'asc'),
                'updated' => $query->orderBy('updated_at', 'desc'),
                'drop_percent' => $query->orderByRaw('(highest_price - current_price) / highest_price DESC'),
                default => $query->orderBy('id', 'desc'),
            };
        }, function ($query) {
            return $query->orderBy('id', 'desc');
        });

        $products = $query->paginate($perPage);

        // Add price change info when filtering by price_change
        if ($priceChange) {
            $productIds = $products->pluck('id')->toArray();
            $priceChangeInfo = $this->getPriceChangeInfo($productIds, $changeDays);

            $products->getCollection()->transform(function ($product) use ($priceChangeInfo) {
                $info = $priceChangeInfo[$product->id] ?? null;
                if ($info) {
                    $product->previous_price = $info['previous_price'];
                    $product->price_change_amount = $info['change_amount'];
                    $product->price_change_percent = $info['change_percent'];
                    $product->price_change_type = $info['change_type'];
                }

                return $product;
            });
        }

        return response()->json($products);
    }

    /**
     * Get price change information for given product IDs.
     */
    private function getPriceChangeInfo(array $productIds, int $days): array
    {
        if (empty($productIds)) {
            return [];
        }

        $cutoffDate = now()->subDays($days);
        $result = [];

        $products = Product::whereIn('id', $productIds)
            ->with(['priceHistories' => function ($query) use ($cutoffDate) {
                $query->where('created_at', '>=', $cutoffDate->subDays(30)) // Get extra history for previous price
                    ->orderBy('created_at', 'desc')
                    ->limit(10);
            }])
            ->get();

        foreach ($products as $product) {
            $histories = $product->priceHistories->sortByDesc('created_at')->values();

            if ($histories->count() < 2) {
                continue;
            }

            $latestPrice = $histories->first()->price;
            $previousPrice = $histories->skip(1)->first()->price;

            if ($latestPrice === $previousPrice) {
                continue;
            }

            $changeAmount = $latestPrice - $previousPrice;
            $changePercent = round(abs($changeAmount) / $previousPrice * 100, 1);
            $changeType = $latestPrice < $previousPrice ? 'dropped' : 'raised';

            $result[$product->id] = [
                'previous_price' => $previousPrice,
                'change_amount' => abs($changeAmount),
                'change_percent' => $changePercent,
                'change_type' => $changeType,
            ];
        }

        return $result;
    }

    /**
     * Get single product details.
     */
    public function show(int $id): JsonResponse
    {
        $product = Product::with(['priceHistories' => function ($query) {
            $query->orderBy('created_at', 'asc')->limit(365);
        }])->findOrFail($id);

        return response()->json([
            'product' => $product,
            'url' => $product->url,
        ]);
    }

    /**
     * Get product price history.
     */
    public function priceHistory(int $id, Request $request): JsonResponse
    {
        $product = Product::findOrFail($id);

        $days = min($request->input('days', 90), 365);

        $history = $product->priceHistories()
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn ($item) => [
                'date' => $item->created_at,
                'price' => $item->price,
            ]);

        return response()->json([
            'product_id' => $product->id,
            'name' => $product->name,
            'current_price' => $product->current_price,
            'lowest_price' => $product->lowest_price,
            'highest_price' => $product->highest_price,
            'history' => $history,
        ]);
    }

    /**
     * Get statistics data.
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total_products' => Product::count(),
            'uniqlo_count' => Product::where('brand', 'uniqlo')->count(),
            'gu_count' => Product::where('brand', 'gu')->count(),
            'genders' => Product::select('gender')
                ->distinct()
                ->whereNotNull('gender')
                ->pluck('gender'),
        ];

        return response()->json($stats);
    }
}

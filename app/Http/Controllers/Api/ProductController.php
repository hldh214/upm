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

        $products = Product::query()
            ->search($request->input('q'))
            ->brand($request->input('brand'))
            ->gender($request->input('gender'))
            ->when($request->input('sort'), function ($query, $sort) {
                return match ($sort) {
                    'price_asc' => $query->orderBy('current_price', 'asc'),
                    'price_desc' => $query->orderBy('current_price', 'desc'),
                    'name' => $query->orderBy('name', 'asc'),
                    'updated' => $query->orderBy('updated_at', 'desc'),
                    default => $query->orderBy('id', 'desc'),
                };
            }, function ($query) {
                return $query->orderBy('id', 'desc');
            })
            ->paginate($perPage);

        return response()->json($products);
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

    /**
     * Get recently price-dropped products.
     *
     * A product is considered "price dropped" if its current price is lower
     * than its previous price record within the specified days.
     */
    public function priceDropped(Request $request): JsonResponse
    {
        $days = min($request->input('days', 7), 30);
        $limit = min($request->input('limit', 20), 50);

        // Get products that have price history records showing a price drop
        $products = Product::query()
            ->whereHas('priceHistories', function ($query) use ($days) {
                $query->where('created_at', '>=', now()->subDays($days));
            })
            ->with(['priceHistories' => function ($query) use ($days) {
                $query->where('created_at', '>=', now()->subDays($days))
                    ->orderBy('created_at', 'desc')
                    ->limit(10);
            }])
            ->get()
            ->filter(function ($product) {
                $histories = $product->priceHistories->sortByDesc('created_at')->values();

                if ($histories->count() < 2) {
                    // If only one record, compare with current price
                    // Current price lower than first history = dropped
                    if ($histories->count() === 1) {
                        return $product->current_price < $histories->first()->price;
                    }
                    return false;
                }

                // Compare latest two price records
                $latestPrice = $histories->first()->price;
                $previousPrice = $histories->skip(1)->first()->price;

                return $latestPrice < $previousPrice;
            })
            ->map(function ($product) {
                $histories = $product->priceHistories->sortByDesc('created_at')->values();
                $previousPrice = $histories->count() >= 2
                    ? $histories->skip(1)->first()->price
                    : ($histories->count() === 1 ? $histories->first()->price : $product->highest_price);

                return [
                    'id' => $product->id,
                    'product_id' => $product->product_id,
                    'price_group' => $product->price_group,
                    'name' => $product->name,
                    'brand' => $product->brand,
                    'gender' => $product->gender,
                    'image_url' => $product->image_url,
                    'current_price' => $product->current_price,
                    'previous_price' => $previousPrice,
                    'lowest_price' => $product->lowest_price,
                    'highest_price' => $product->highest_price,
                    'drop_amount' => $previousPrice - $product->current_price,
                    'drop_percentage' => round((1 - $product->current_price / $previousPrice) * 100, 1),
                    'dropped_at' => $histories->first()->created_at->toIso8601String(),
                ];
            })
            ->sortByDesc('drop_percentage')
            ->take($limit)
            ->values();

        return response()->json([
            'data' => $products,
            'total' => $products->count(),
            'days' => $days,
        ]);
    }
}

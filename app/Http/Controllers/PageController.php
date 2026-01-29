<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    /**
     * Homepage - Product list.
     */
    public function index(Request $request): Response
    {
        $perPage = min($request->input('per_page', 24), 100);

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

        // Get stats
        $stats = [
            'total_products' => Product::count(),
            'dropped_count' => Product::priceChange('dropped', 3)->count(),
        ];

        return Inertia::render('Products/Index', [
            'products' => $products,
            'stats' => $stats,
            'filters' => [
                'q' => $request->input('q', ''),
                'brand' => $request->input('brand', ''),
                'gender' => $request->input('gender', ''),
                'sort' => $request->input('sort', ''),
                'price_change' => $priceChange,
                'change_days' => $changeDays,
            ],
        ]);
    }

    /**
     * Get price change information for given product IDs.
     */
    private function getPriceChangeInfo(array $productIds, int $days): array
    {
        if (empty($productIds)) {
            return [];
        }

        $startDate = now()->subDays($days)->startOfDay();
        $result = [];

        $products = Product::whereIn('id', $productIds)
            ->with(['priceHistories' => function ($query) {
                $query->orderBy('created_at', 'desc')->limit(10);
            }])
            ->get();

        foreach ($products as $product) {
            $histories = $product->priceHistories->sortBy('created_at')->values();

            if ($histories->count() < 2) {
                continue;
            }

            $recordsInPeriod = $histories->filter(fn ($h) => $h->created_at >= $startDate);

            if ($recordsInPeriod->isEmpty()) {
                continue;
            }

            $latestChange = $recordsInPeriod->last();
            $latestIndex = $histories->search(fn ($h) => $h->id === $latestChange->id);

            if ($latestIndex === false || $latestIndex === 0) {
                continue;
            }

            $previousRecord = $histories[$latestIndex - 1];
            $previousPrice = $previousRecord->price;
            $newPrice = $latestChange->price;

            if ($previousPrice === $newPrice) {
                continue;
            }

            $changeAmount = $newPrice - $previousPrice;
            $changePercent = round(abs($changeAmount) / $previousPrice * 100, 1);
            $changeType = $newPrice < $previousPrice ? 'dropped' : 'raised';

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
     * Product detail page.
     */
    public function show(int $id): Response
    {
        $product = Product::with(['priceHistories' => function ($query) {
            $query->orderBy('created_at', 'asc');
        }])->findOrFail($id);

        // Format price history
        $history = $product->priceHistories
            ->sortBy('created_at')
            ->values()
            ->map(fn ($item) => [
                'date' => $item->created_at,
                'price' => $item->price,
            ]);

        // Get watchlist count
        $watchlistCount = $product->watchlists()->count();

        return Inertia::render('Products/Show', [
            'product' => $product,
            'history' => $history,
            'watchlistCount' => $watchlistCount,
        ]);
    }
}

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
            ->map(function ($item) {
                return [
                    'date' => $item->created_at->format('Y-m-d H:i:s'),
                    'price' => $item->price,
                ];
            });

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

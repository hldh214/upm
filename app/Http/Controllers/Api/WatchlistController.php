<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Watchlist;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WatchlistController extends Controller
{
    /**
     * Get all watchlist items for authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $watchlists = Watchlist::where('user_id', $user->id)
            ->with('product')
            ->with('notificationSettings')
            ->get();

        return response()->json($watchlists);
    }

    /**
     * Add product to watchlist.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        // Check if already in watchlist
        $exists = Watchlist::where('user_id', $user->id)
            ->where('product_id', $validated['product_id'])
            ->exists();

        if ($exists) {
            return response()->json(['error' => 'Already in watchlist'], 400);
        }

        $watchlist = Watchlist::create([
            'user_id' => $user->id,
            'product_id' => $validated['product_id'],
        ]);

        return response()->json($watchlist->load('product'), 201);
    }

    /**
     * Remove product from watchlist.
     */
    public function destroy(Request $request, int $productId): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $watchlist = Watchlist::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->first();

        if (!$watchlist) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $watchlist->delete();

        return response()->json(null, 204);
    }

    /**
     * Check if product is in user's watchlist.
     */
    public function check(Request $request, int $productId): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['inWatchlist' => false]);
        }

        $inWatchlist = Watchlist::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->exists();

        return response()->json(['inWatchlist' => $inWatchlist]);
    }

    /**
     * Get watchlist count for a product.
     */
    public function count(int $productId): JsonResponse
    {
        $count = Watchlist::where('product_id', $productId)->count();

        return response()->json(['count' => $count]);
    }
}

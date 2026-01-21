<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    /**
     * Homepage - Product list.
     */
    public function index(Request $request): View
    {
        return view('products.index');
    }

    /**
     * Product detail page.
     */
    public function show(int $id): View
    {
        $product = Product::with(['priceHistories' => function ($query) {
            $query->orderBy('created_at', 'asc');
        }])->findOrFail($id);

        return view('products.show', compact('product'));
    }
}

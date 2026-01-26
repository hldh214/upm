<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'price_group',
        'name',
        'brand',
        'gender',
        'image_url',
        'current_price',
        'lowest_price',
        'highest_price',
    ];

    protected $casts = [
        'current_price' => 'integer',
        'lowest_price' => 'integer',
        'highest_price' => 'integer',
    ];

    /**
     * Get the price histories for this product.
     */
    public function priceHistories(): HasMany
    {
        return $this->hasMany(PriceHistory::class);
    }

    /**
     * Get the product detail page URL.
     */
    public function getUrlAttribute(): string
    {
        $baseUrl = $this->brand === 'uniqlo'
            ? 'https://www.uniqlo.com/jp/ja/products/'
            : 'https://www.gu-global.com/jp/ja/products/';

        return $baseUrl.$this->product_id.'/'.$this->price_group;
    }

    /**
     * Scope to search products by keyword.
     */
    public function scopeSearch($query, ?string $keyword)
    {
        if ($keyword) {
            return $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('product_id', 'like', "%{$keyword}%");
            });
        }

        return $query;
    }

    /**
     * Scope to filter by brand.
     */
    public function scopeBrand($query, ?string $brand)
    {
        if ($brand) {
            return $query->where('brand', $brand);
        }

        return $query;
    }

    /**
     * Scope to filter by gender.
     */
    public function scopeGender($query, ?string $gender)
    {
        if ($gender) {
            return $query->where('gender', $gender);
        }

        return $query;
    }

    /**
     * Scope to filter by recent price changes.
     *
     * Compares current_price with the price from N days ago.
     *
     * @param  array|string|null  $priceChange  'dropped', 'raised', or ['dropped', 'raised']
     * @param  int  $days  Number of days to look back
     */
    public function scopePriceChange($query, $priceChange, int $days = 7)
    {
        if (empty($priceChange)) {
            return $query;
        }

        // Normalize to array
        $changes = is_array($priceChange) ? $priceChange : [$priceChange];
        $validChanges = array_intersect($changes, ['dropped', 'raised']);

        if (empty($validChanges)) {
            return $query;
        }

        $cutoffDate = now()->subDays($days);

        // Find products that have a price history record from before cutoff
        // and compare that old price with current_price
        return $query->whereHas('priceHistories', function ($q) use ($cutoffDate, $validChanges) {
            $q->where('created_at', '<', $cutoffDate)
                ->where(function ($q) use ($validChanges) {
                    if (in_array('dropped', $validChanges)) {
                        // Old price > current price = dropped
                        $q->orWhereColumn('price', '>', 'products.current_price');
                    }
                    if (in_array('raised', $validChanges)) {
                        // Old price < current price = raised
                        $q->orWhereColumn('price', '<', 'products.current_price');
                    }
                });
        });
    }
}

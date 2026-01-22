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

        return $query->whereHas('priceHistories', function ($q) use ($cutoffDate) {
            $q->where('created_at', '>=', $cutoffDate);
        })
            ->where(function ($q) use ($validChanges, $cutoffDate) {
                // Use subquery to get the latest price history before cutoff and compare
                $q->whereRaw('EXISTS (
                SELECT 1 FROM (
                    SELECT 
                        ph1.product_id,
                        ph1.price as latest_price,
                        (SELECT ph2.price FROM price_histories ph2 
                         WHERE ph2.product_id = ph1.product_id 
                         AND ph2.created_at < ph1.created_at 
                         ORDER BY ph2.created_at DESC LIMIT 1) as previous_price
                    FROM price_histories ph1
                    WHERE ph1.product_id = products.id
                    AND ph1.created_at >= ?
                    ORDER BY ph1.created_at DESC
                    LIMIT 1
                ) as price_change
                WHERE price_change.previous_price IS NOT NULL
                AND (
                    '.$this->buildPriceChangeCondition($validChanges).'
                )
            )', [$cutoffDate]);
            });
    }

    /**
     * Build SQL condition for price change types.
     */
    private function buildPriceChangeCondition(array $changes): string
    {
        $conditions = [];
        if (in_array('dropped', $changes)) {
            $conditions[] = 'price_change.latest_price < price_change.previous_price';
        }
        if (in_array('raised', $changes)) {
            $conditions[] = 'price_change.latest_price > price_change.previous_price';
        }

        return implode(' OR ', $conditions);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

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
     * Finds products that had a price change within the specified period.
     * For "last N days", it looks from (N days ago at 00:00) to now.
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

        // Start from N days ago at 00:00:00
        $startDate = now()->subDays($days)->startOfDay();

        // Find products that have price changes within the period
        // A price change means: there exists a record within the period whose price
        // differs from a previous record (either before or within the period)
        return $query->whereIn('id', function ($subquery) use ($startDate, $validChanges) {
            $subquery->select('ph_new.product_id')
                ->from('price_histories as ph_new')
                ->join('price_histories as ph_old', function ($join) {
                    $join->on('ph_new.product_id', '=', 'ph_old.product_id')
                        ->on('ph_old.created_at', '<', 'ph_new.created_at');
                })
                // The newer record must be within our period
                ->where('ph_new.created_at', '>=', $startDate)
                // Get the most recent old record for comparison
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('price_histories as ph_between')
                        ->whereColumn('ph_between.product_id', 'ph_new.product_id')
                        ->whereColumn('ph_between.created_at', '>', 'ph_old.created_at')
                        ->whereColumn('ph_between.created_at', '<', 'ph_new.created_at');
                })
                ->where(function ($q) use ($validChanges) {
                    if (in_array('dropped', $validChanges)) {
                        // New price < old price = dropped
                        $q->orWhereColumn('ph_new.price', '<', 'ph_old.price');
                    }
                    if (in_array('raised', $validChanges)) {
                        // New price > old price = raised
                        $q->orWhereColumn('ph_new.price', '>', 'ph_old.price');
                    }
                })
                ->distinct();
        });
    }
}

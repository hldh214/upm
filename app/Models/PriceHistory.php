<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'price',
    ];

    protected $casts = [
        'price' => 'integer',
    ];

    /**
     * Get the product that owns this price history.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

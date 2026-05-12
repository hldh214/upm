<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceNotification extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'notification_type',
        'price_at_notification',
    ];

    protected $casts = [
        'price_at_notification' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    protected $fillable = [
        'user_id',
        'watchlist_id',
        'price_drop_enabled',
        'price_drop_target',
        'price_change_enabled',
        'price_change_min_amount',
        'new_product_enabled',
    ];

    protected $casts = [
        'price_drop_enabled' => 'boolean',
        'price_change_enabled' => 'boolean',
        'new_product_enabled' => 'boolean',
        'price_drop_target' => 'integer',
        'price_change_min_amount' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function watchlist()
    {
        return $this->belongsTo(Watchlist::class);
    }
}

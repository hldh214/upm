<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewProductNotificationSetting extends Model
{
    protected $fillable = [
        'user_id',
        'brand',
        'gender',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

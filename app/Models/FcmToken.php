<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FcmToken extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'device_info',
        'platform',
        'active',
        'last_used_at'
    ];

    protected $casts = [
        'active' => 'boolean',
        'last_used_at' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}

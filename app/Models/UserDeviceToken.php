<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class UserDeviceToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_token',
        'device_type',
        'last_used_at',
        'device_name',
        'last_location',
    ];

    /**
     * Get the user that owns this device token.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
} 
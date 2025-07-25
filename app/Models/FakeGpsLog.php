<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FakeGpsLog extends Model
{
    use HasFactory;

    protected $table = 'fake_gps_logs';

    protected $fillable = [
        'user_id',
        'latitude',
        'longitude',
        'device_info',
        'ip_address',
        'detected_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
} 
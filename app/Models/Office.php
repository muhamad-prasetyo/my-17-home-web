<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Office extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'address',
        'start_time',
        'end_time',
        'latitude',
        'longitude',
        'radius_meter',
        'type',
        'office_type',
    ];

    /**
     * Get the schedules for the office.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }
}

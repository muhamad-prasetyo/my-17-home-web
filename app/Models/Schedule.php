<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Company;
use App\Models\User;
use App\Models\Attendance;
use App\Models\TransferRequest;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'schedule_name',
        'start_time',
        'end_time',
        'working_days',
        'is_active',
    ];

  

    /**
     * Get the users assigned to this schedule.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the attendances for this schedule.
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Get transfer requests where this schedule is the source.
     */
    public function transferRequestsFrom()
    {
        return $this->hasMany(TransferRequest::class, 'current_schedule_id');
    }

    /**
     * Get transfer requests where this schedule is the target.
     */
    public function transferRequestsTo()
    {
        return $this->hasMany(TransferRequest::class, 'target_schedule_id');
    }

    /**
     * Get the office that owns the schedule.
     */
    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }
}

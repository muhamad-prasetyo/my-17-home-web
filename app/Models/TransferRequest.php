<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Schedule;
use App\Events\TransferRequestCreated;

class TransferRequest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'current_schedule_id',
        'target_schedule_id',
        'reason',
        'request_date',
        'effective_date',
        'status',
        'approved_by_user_id',
        'approval_date',
        'rejection_reason',
    ];

    /**
     * Dispatch event when a new transfer request is created.
     */
    protected static function booted(): void
    {
        static::created(function (TransferRequest $transferRequest) {
            event(new TransferRequestCreated($transferRequest));
        });
    }

    /**
     * The user who is being transferred.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The schedule from which the user is transferred.
     */
    public function currentSchedule()
    {
        return $this->belongsTo(Schedule::class, 'current_schedule_id');
    }

    /**
     * The schedule to which the user is transferred.
     */
    public function targetSchedule()
    {
        return $this->belongsTo(Schedule::class, 'target_schedule_id');
    }

    /**
     * The user who approved the transfer.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}

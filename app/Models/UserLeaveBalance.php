<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLeaveBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'leave_type_id',
        'year',
        'entitled_days',
        'taken_days',
    ];

    /**
     * The user owning this balance.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The leave type of this balance.
     */
    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }
} 
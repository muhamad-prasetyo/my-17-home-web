<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'requires_balance',
        'is_active',
    ];

    /**
     * The leave requests associated with this type.
     */
    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /**
     * The user leave balances associated with this type.
     */
    public function userLeaveBalances()
    {
        return $this->hasMany(UserLeaveBalance::class);
    }
} 
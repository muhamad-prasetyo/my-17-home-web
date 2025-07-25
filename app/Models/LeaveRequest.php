<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'reason',
        'status',
        'approved_by_user_id',
        'approval_date',
        'rejection_reason',
        'attachment_path',
    ];

    /**
     * The user who requested the leave.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The leave type of the request.
     */
    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    /**
     * The user who approved the leave.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
} 
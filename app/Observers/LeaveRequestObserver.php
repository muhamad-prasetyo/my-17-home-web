<?php

namespace App\Observers;

use App\Models\LeaveRequest;
use App\Models\UserLeaveBalance;
use App\Events\LeaveRequestApproved;
use App\Events\LeaveRequestCreated;
use App\Events\LeaveRequestRejected;
use App\Events\LeaveRequestCancelled;
use App\Services\WorkCalendarService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class LeaveRequestObserver
{
    /**
     * Handle the LeaveRequest "updated" event.
     */
    public function updated(LeaveRequest $leaveRequest): void
    {
        if ($leaveRequest->isDirty('status')) {
            $newStatus = $leaveRequest->status;
            Log::info("[OBSERVER] LeaveRequest ID: {$leaveRequest->id} status changed to: {$newStatus}. Dispatching corresponding event.");
            switch ($newStatus) {
                case 'approved':
                    event(new LeaveRequestApproved($leaveRequest));
                    break;
                case 'rejected':
                    event(new LeaveRequestRejected($leaveRequest));
                    break;
                case 'cancelled':
                    event(new LeaveRequestCancelled($leaveRequest));
                    break;
                default:
                    // other statuses
                    event(new LeaveRequestCreated($leaveRequest));
                    break;
            }
        }
    }

    /**
     * Handle the LeaveRequest "created" event.
     */
    public function created(LeaveRequest $leaveRequest): void
    {
        Log::info("[OBSERVER] LeaveRequest ID: {$leaveRequest->id} created. Dispatching LeaveRequestCreated event.");
        event(new LeaveRequestCreated($leaveRequest));
    }

    /**
     * Handle the LeaveRequest "deleted" event.
     */
    // public function deleted(LeaveRequest $leaveRequest): void
    // {
    //     //
    // }

    /**
     * Handle the LeaveRequest "restored" event.
     */
    // public function restored(LeaveRequest $leaveRequest): void
    // {
    //     //
    // }

    /**
     * Handle the LeaveRequest "force deleted" event.
     */
    // public function forceDeleted(LeaveRequest $leaveRequest): void
    // {
    //     //
    // }
} 
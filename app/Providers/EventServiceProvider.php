<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        // LeaveRequest status events
        \App\Events\LeaveRequestCreated::class => [
            \App\Listeners\SendLeaveRequestNotification::class,
        ],
        \App\Events\LeaveRequestApproved::class => [
            \App\Listeners\SendLeaveRequestNotification::class,
            \App\Listeners\GenerateLeaveAttendanceEntries::class,
        ],
        \App\Events\LeaveRequestRejected::class => [
            \App\Listeners\SendLeaveRequestNotification::class,
        ],
        \App\Events\LeaveRequestCancelled::class => [
            \App\Listeners\SendLeaveRequestNotification::class,
        ],
        \App\Events\TransferRequestApproved::class => [
            \App\Listeners\SendTransferRequestNotification::class,
        ],
        \App\Events\TransferRequestCreated::class => [
            \App\Listeners\SendPendingTransferRequestNotification::class,
        ],
        \App\Events\PermissionRequestCreated::class => [
            \App\Listeners\SendPermissionRequestNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
} 
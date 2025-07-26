<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SendFirebaseNotification;
use App\Jobs\ProcessAttendanceReport;

class TestQueueCommand extends Command
{
    protected $signature = 'queue:test';
    protected $description = 'Test queue system by dispatching sample jobs';

    public function handle()
    {
        $this->info('🧪 Testing Queue System...');

        // Test Firebase Notification Job
        $this->info('📱 Dispatching Firebase Notification Job...');
        SendFirebaseNotification::dispatch(
            1, // userId
            'Test Notification', // title
            'Queue system is working!', // body
            [], // data
            null // attendanceId
        );

        // Test Attendance Report Job
        $this->info('📊 Dispatching Attendance Report Job...');
        ProcessAttendanceReport::dispatch(1, date('Y-m-d'));

        $this->info('✅ Jobs dispatched! Check queue status with: php artisan queue:work --once');
        
        // Show queue status
        $this->info('📊 Current queue status:');
        $this->call('queue:work', ['--once' => true]);
    }
}

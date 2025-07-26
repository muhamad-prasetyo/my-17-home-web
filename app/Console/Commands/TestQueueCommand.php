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
        SendFirebaseNotification::dispatch([
            'title' => 'Test Notification',
            'body' => 'Queue system is working!',
            'user_id' => 1
        ]);

        // Test Attendance Report Job
        $this->info('📊 Dispatching Attendance Report Job...');
        ProcessAttendanceReport::dispatch(1, date('Y-m-d'));

        $this->info('✅ Jobs dispatched! Check queue status with: php artisan queue:monitor');
        
        // Show queue status
        $this->call('queue:monitor');
    }
}

<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\UserDeviceToken;
use App\Models\Attendance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Illuminate\Support\Facades\Log;

class SendFirebaseNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;
    protected $title;
    protected $body;
    protected $data;
    protected $attendanceId;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 30;

    /**
     * Create a new job instance.
     *
     * @param int $userId
     * @param string $title
     * @param string $body
     * @param array $data
     * @param int|null $attendanceId
     */
    public function __construct(int $userId, string $title, string $body, array $data = [], ?int $attendanceId = null)
    {
        $this->userId = $userId;
        $this->title = $title;
        $this->body = $body;
        $this->data = $data;
        $this->attendanceId = $attendanceId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Get user device tokens
            $deviceTokens = UserDeviceToken::where('user_id', $this->userId)
                ->pluck('token')
                ->toArray();

            if (empty($deviceTokens)) {
                Log::info("No device tokens found for user ID: {$this->userId}");
                return;
            }

            // Initialize Firebase Messaging
            $messaging = app('firebase.messaging');
            
            // Create notification
            $notification = FirebaseNotification::create($this->title, $this->body);
            
            // Add attendance ID to data if provided
            if ($this->attendanceId) {
                $this->data['attendance_id'] = $this->attendanceId;
            }
            
            // Send to each token
            foreach ($deviceTokens as $token) {
                try {
                    $message = CloudMessage::withTarget('token', $token)
                        ->withNotification($notification)
                        ->withData($this->data);
                    
                    $messaging->send($message);
                    
                    Log::info("Firebase notification sent to device token: {$token}");
                } catch (\Exception $e) {
                    Log::error("Error sending notification to token {$token}: " . $e->getMessage());
                    
                    // Check if token is invalid and remove it
                    if (strpos($e->getMessage(), 'invalid-registration-token') !== false ||
                        strpos($e->getMessage(), 'registration-token-not-registered') !== false) {
                        UserDeviceToken::where('token', $token)->delete();
                        Log::info("Invalid token removed: {$token}");
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Error in SendFirebaseNotification job: " . $e->getMessage());
            throw $e;
        }
    }
}

<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ProcessAttendanceReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $startDate;
    protected $endDate;
    protected $userId;
    protected $companyId;
    protected $reportType;
    protected $filters;
    protected $notifyUserId;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 300; // 5 minutes timeout for large reports

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $startDate, 
        string $endDate, 
        ?int $userId = null, 
        ?int $companyId = null, 
        string $reportType = 'csv',
        array $filters = [],
        ?int $notifyUserId = null
    ) {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->userId = $userId;
        $this->companyId = $companyId;
        $this->reportType = $reportType;
        $this->filters = $filters;
        $this->notifyUserId = $notifyUserId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("Starting attendance report generation for period: {$this->startDate} to {$this->endDate}");
            
            // Generate unique report ID
            $reportId = md5(json_encode([
                'startDate' => $this->startDate,
                'endDate' => $this->endDate,
                'userId' => $this->userId,
                'companyId' => $this->companyId,
                'filters' => $this->filters,
                'timestamp' => time()
            ]));
            
            // Set status as processing
            Cache::put("report_status_{$reportId}", 'processing', 3600);
            
            // Start building query with eager loading and chunking for performance
            $query = Attendance::with(['user', 'schedule', 'sourceOffice', 'destinationOffice'])
                ->whereBetween('date', [$this->startDate, $this->endDate]);
            
            if ($this->userId) {
                $query->where('user_id', $this->userId);
            }
            
            if ($this->companyId) {
                $query->where('company_id', $this->companyId);
            }
            
            // Apply any additional filters
            foreach ($this->filters as $key => $value) {
                if ($value !== null && $value !== '') {
                    $query->where($key, $value);
                }
            }
            
            // Initialize report data storage
            $reportData = [];
            $reportFile = "reports/attendance_{$reportId}.{$this->reportType}";
            
            // Process in chunks to avoid memory issues
            $query->orderBy('date')->orderBy('user_id')
                ->chunk(500, function ($attendances) use (&$reportData) {
                    foreach ($attendances as $attendance) {
                        $reportData[] = [
                            'date' => $attendance->date,
                            'user_id' => $attendance->user_id,
                            'name' => $attendance->user ? $attendance->user->name : 'Unknown',
                            'time_in' => $attendance->time_in,
                            'time_out' => $attendance->time_out,
                            'is_late' => $attendance->is_late ? 'Yes' : 'No',
                            'late_duration' => $attendance->late_duration ?? 0,
                            'schedule' => $attendance->schedule ? $attendance->schedule->name : 'N/A',
                            'attendance_type' => $attendance->attendance_type,
                            'status' => $attendance->status_attendance,
                            'transfer_status' => $attendance->is_transfer_day ? $attendance->transfer_status : 'N/A',
                        ];
                    }
                });
            
            // Generate report file based on type
            if ($this->reportType === 'csv') {
                $this->generateCsvReport($reportData, $reportFile);
            } elseif ($this->reportType === 'json') {
                $this->generateJsonReport($reportData, $reportFile);
            }
            
            // Set status as completed with file info
            Cache::put("report_status_{$reportId}", [
                'status' => 'completed',
                'file' => $reportFile,
                'count' => count($reportData),
                'generated_at' => now()->toDateTimeString()
            ], 3600 * 24); // Keep for 24 hours
            
            // Notify user if needed
            if ($this->notifyUserId) {
                $this->notifyUser($reportId);
            }
            
            Log::info("Attendance report generated successfully: {$reportFile}");
        } catch (\Exception $e) {
            Log::error("Error generating attendance report: " . $e->getMessage());
            if (isset($reportId)) {
                Cache::put("report_status_{$reportId}", [
                    'status' => 'failed',
                    'error' => $e->getMessage()
                ], 3600);
            }
            throw $e;
        }
    }
    
    /**
     * Generate CSV report
     */
    private function generateCsvReport(array $data, string $filePath): void
    {
        if (empty($data)) {
            Storage::put($filePath, "No data found for the selected period.\n");
            return;
        }
        
        $handle = fopen('php://temp', 'r+');
        
        // Add headers
        fputcsv($handle, array_keys($data[0]));
        
        // Add data rows
        foreach ($data as $row) {
            fputcsv($handle, $row);
        }
        
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);
        
        Storage::put($filePath, $csv);
    }
    
    /**
     * Generate JSON report
     */
    private function generateJsonReport(array $data, string $filePath): void
    {
        Storage::put($filePath, json_encode([
            'data' => $data,
            'count' => count($data),
            'generated_at' => now()->toDateTimeString()
        ]));
    }
    
    /**
     * Notify user about completed report
     */
    private function notifyUser(string $reportId): void
    {
        $user = User::find($this->notifyUserId);
        if (!$user) return;
        
        // Dispatch notification job
        SendFirebaseNotification::dispatch(
            $this->notifyUserId,
            'Report Generated',
            'Your attendance report is ready for download',
            ['report_id' => $reportId, 'type' => 'report_completed']
        );
    }
}

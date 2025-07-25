<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Attendance extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'schedule_id',
        'company_id',
        'date',
        'time_in',
        'time_out',
        'latlon_in',
        'latlon_out',
        'attendance_type',
        'status_attendance',
        'is_late',
        'late_duration',
        'late_reason',
        'original_schedule_id',
        'related_attendance_id',
        'is_transfer_day',
        'transfer_request_id',
        'source_office_id',
        'source_time_in',
        'source_time_out',
        'source_latlon_in',
        'source_latlon_out',
        'destination_office_id',
        'destination_time_in',
        'destination_time_out',
        'destination_latlon_in',
        'destination_latlon_out',
        'transfer_status',
        'selfie_image_in',
        'selfie_image_out',
        'status_attendance_out',
        'checkout_reason',
        'is_early_checkout',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_late' => 'boolean',
        'late_duration' => 'integer',
        'date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'is_transfer_day' => 'boolean',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Get the user for this attendance.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the schedule for this attendance.
     */
    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    /**
     * Get the original schedule for this attendance (for transfers).
     */
    public function originalSchedule()
    {
        return $this->belongsTo(Schedule::class, 'original_schedule_id');
    }

    /**
     * Get the related attendance record (for transfers).
     */
    public function relatedAttendance()
    {
        return $this->belongsTo(Attendance::class, 'related_attendance_id');
    }

    /**
     * Get the company for this attendance.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Check if the attendance is late based on schedule
     */
    public function checkLateness()
    {
        if (!$this->schedule || !$this->time_in) {
            return false;
        }

        $scheduleTime = Carbon::parse($this->schedule->time_in);
        $actualTimeIn = Carbon::parse($this->time_in);
        
        if ($actualTimeIn->gt($scheduleTime)) {
            $this->is_late = true;
            $this->late_duration = $actualTimeIn->diffInMinutes($scheduleTime);
            return true;
        }

        return false;
    }
    
    /**
     * The "booted" method of the model.
     * 
     * Use this to apply global scopes or register event listeners.
     */
    protected static function booted()
    {
        // Ensure status_attendance has a default value if not set
        static::creating(function ($attendance) {
            if (empty($attendance->status_attendance)) {
                $attendance->status_attendance = 'present';
            }
        });
    }
    
    /**
     * Format time for display
     * 
     * @param string|null $time
     * @return string
     */
    public function formatTime($time)
    {
        if (empty($time)) return '--:--';
        
        try {
            return Carbon::parse($time)->format('H:i');
        } catch (\Exception $e) {
            return $time;
        }
    }

    /**
     * Get formatted time_in attribute
     * 
     * @return string
     */
    public function getFormattedTimeInAttribute()
    {
        return $this->formatTime($this->time_in);
    }
    
    /**
     * Get formatted time_out attribute
     * 
     * @return string
     */
    public function getFormattedTimeOutAttribute()
    {
        return $this->formatTime($this->time_out);
    }
    
    /**
     * Calculate total work duration in minutes
     * 
     * @return int|null
     */
    public function getWorkDurationAttribute()
    {
        if (empty($this->time_in) || empty($this->time_out)) {
            return null;
        }
        
        try {
            $timeIn = Carbon::parse($this->time_in);
            $timeOut = Carbon::parse($this->time_out);
            
            // Check if timeOut is before timeIn (invalid case)
            if ($timeOut->lt($timeIn)) {
                return null;
            }
            
            return $timeOut->diffInMinutes($timeIn);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get the transfer request for this attendance.
     */
    public function transferRequest()
    {
        return $this->belongsTo(TransferRequest::class);
    }

    /**
     * Get the source office for this attendance.
     */
    public function sourceOffice()
    {
        return $this->belongsTo(Office::class, 'source_office_id');
    }

    /**
     * Get the destination office for this attendance.
     */
    public function destinationOffice()
    {
        return $this->belongsTo(Office::class, 'destination_office_id');
    }

    /**
     * Define the relationship to the LeaveRequest model.
     * An attendance record is associated with a leave request if the user and date match.
     */
    public function leaveRequest()
    {
        return $this->hasOne(LeaveRequest::class, 'user_id', 'user_id')
            ->whereDate('start_date', '<=', $this->date)
            ->whereDate('end_date', '>=', $this->date)
            ->where('status', 'approved');
    }
}

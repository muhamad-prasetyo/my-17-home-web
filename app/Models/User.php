<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\HasAvatar;
use App\Models\Schedule;
use App\Models\Attendance;
use App\Models\UserDeviceToken;
use App\Models\TransferRequest;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Notifications\DatabaseNotification;

/**
 * @property \Illuminate\Database\Eloquent\Relations\MorphMany $notifications
 * @method \Illuminate\Database\Eloquent\Builder unreadNotifications()
 */
class User extends Authenticatable implements HasAvatar, MustVerifyEmail
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
        'position',
        'department',
        'face_embedding',
        'image_url',
        'avatar',
        'fcm_token',
        'schedule_id',
        'tanggal_lahir',
        'kewarganegaraan',
        'agama',
        'jenis_kelamin',
        'status_pernikahan',
        'waktu_kontrak',
        'tinggi_badan',
        'berat_badan',
        'golongan_darah',
        'gangguan_penglihatan',
        'buta_warna',
        'is_wfa',
        'is_approved', // <-- tambahkan ini
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        // 'face_embedding', // Jangan sembunyikan, diperlukan untuk fitur face recognition di mobile
 ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'avatar',
        'profile_photo_url',
    ];

    /**
     * Default eager loaded relationships.
     *
     * @var array
     */
    protected $with = [
        'schedule',
    ];

    /**
     * The relationships that should be cached for performance.
     *
     * @var array
     */
    protected $cacheRelationships = [
        'schedule',
        'transferRequests',
    ];

    /**
     * Accessor to map image_url column to avatar attribute for API.
     */
    public function getAvatarAttribute(): ?string
    {
        return $this->image_url;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'tanggal_lahir' => 'date',
        'tinggi_badan' => 'integer',
        'berat_badan' => 'integer',
        'is_wfa' => 'boolean',
    ];
    
    /**
     * Get the attendances for the user with appropriate select columns.
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class)
            ->select([
                'id', 'user_id', 'date', 'time_in', 'time_out', 
                'attendance_type', 'status_attendance', 'is_late', 
                'late_duration', 'schedule_id', 'is_transfer_day', 
                'transfer_request_id'
            ]);
    }
    
    /**
     * Get the attendances for a specific date range with optimized query.
     * 
     * @param string $startDate
     * @param string $endDate
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAttendancesForDateRange(string $startDate, string $endDate)
    {
        $cacheKey = "user_{$this->id}_attendances_{$startDate}_{$endDate}";
        
        return Cache::remember($cacheKey, 300, function () use ($startDate, $endDate) {
            return $this->attendances()
                ->with(['schedule:id,name,start_time,end_time', 'sourceOffice:id,name', 'destinationOffice:id,name'])
                ->whereBetween('date', [$startDate, $endDate])
                ->orderBy('date', 'desc')
                ->get();
        });
    }
    
    /**
     * Get today's attendance record with optimized query.
     * 
     * @return \App\Models\Attendance|null
     */
    public function getTodayAttendance()
    {
        $today = Carbon::now()->toDateString();
        $cacheKey = "user_{$this->id}_attendance_{$today}";
        
        return Cache::remember($cacheKey, 300, function () use ($today) {
            return $this->attendances()
                ->with(['schedule:id,name,start_time,end_time'])
                ->where('date', $today)
                ->first();
        });
    }
    
    /**
     * Get the schedule assigned to the user.
     */
    public function schedule()
    {
        return $this->belongsTo(Schedule::class)
            ->with('office:id,name,latitude,longitude,radius_meter');
    }

    /**
     * Get the permission requests for the user.
     */
    public function permissionRequests()
    {
        return $this->hasMany(Permission::class, 'user_id')
            ->select(['id', 'user_id', 'is_approved', 'date_permission', 'type_permission', 'reason_permission', 'created_at']);
    }

    /**
     * Accessor for avatar_url for API and Filament.
     * This is the single source of truth for the user's avatar URL.
     */
    public function getAvatarUrlAttribute()
    {
        $path = $this->image_url ?? $this->avatar;
        if ($path) {
            if (filter_var($path, FILTER_VALIDATE_URL)) {
                return $path;
            }
            // Jika path sudah mengandung 'storage/', langsung asset
            if (str_starts_with($path, 'storage/')) {
                return asset($path);
            }
            // Jika file ada di public/images/users, pakai langsung
            if (file_exists(public_path($path))) {
                return asset($path);
            }
            // Jika file tidak ada di public/images/users, coba storage/images/users
            if (str_starts_with($path, 'images/users')) {
                $storagePath = 'storage/' . $path;
                if (file_exists(public_path($storagePath))) {
                    return asset($storagePath);
                }
            }
            // Fallback default
            return asset('images/users/default_avatar.png');
        }
        return asset('images/users/default_avatar.png');
    }

    /**
     * Return the URL for the user's avatar for Filament, satisfying the HasAvatar contract.
     */
    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_url;
    }

    /**
     * Get the device tokens for the user.
     */
    public function deviceTokens()
    {
        return $this->hasMany(UserDeviceToken::class);
    }

    /**
     * Get the transfer requests for the user with eager loading.
     */
    public function transferRequests()
    {
        return $this->hasMany(TransferRequest::class, 'user_id')
            ->with(['currentSchedule.office', 'targetSchedule.office']);
    }
    
    /**
     * Get the active transfer requests for today.
     * 
     * @return \App\Models\TransferRequest|null
     */
    public function getActiveTodayTransfer()
    {
        $today = Carbon::now()->toDateString();
        $cacheKey = "user_{$this->id}_active_transfer_{$today}";
        
        return Cache::remember($cacheKey, 300, function () use ($today) {
            return $this->transferRequests()
                ->where('status', 'approved')
                ->where('effective_date', $today)
                ->first();
        });
    }
    
    /**
     * Check if user has an approved leave for today
     * 
     * @return bool
     */
    public function hasApprovedLeaveToday()
    {
        $today = Carbon::now()->toDateString();
        $cacheKey = "user_{$this->id}_leave_today";
        
        return Cache::remember($cacheKey, 300, function () use ($today) {
            return LeaveRequest::where('user_id', $this->id)
                ->where('status', 'approved')
                ->where(function ($query) use ($today) {
                    $query->where('start_date', '<=', $today)
                          ->where('end_date', '>=', $today);
                })
                ->exists();
        });
    }

    /**
     * Get all notifications for the user.
     */
    public function notifications()
    {
        return $this->morphMany(DatabaseNotification::class, 'notifiable')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get unread notifications for the user.
     */
    public function unreadNotifications()
    {
        return $this->notifications()->whereNull('read_at');
    }

    /**
     * Get the day offs for the user.
     */
    public function dayOffs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserDayOff::class);
    }

    /**
     * Accessor untuk profile_photo_url agar Filament v2 bisa ambil avatar global.
     */
    public function getProfilePhotoUrlAttribute()
    {
        return $this->getAvatarUrlAttribute();
    }
}

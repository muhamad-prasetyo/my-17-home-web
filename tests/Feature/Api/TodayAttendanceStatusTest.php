<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Office;
use App\Models\Schedule;
use App\Models\Attendance;
use App\Models\TransferRequest;
use Carbon\Carbon;
use Laravel\Sanctum\Sanctum;

class TodayAttendanceStatusTest extends TestCase
{
    use RefreshDatabase; // Menggunakan RefreshDatabase untuk me-reset database setiap selesai test

    protected User $user;
    protected Office $officeFrom;
    protected Office $officeTo;
    protected Schedule $scheduleFrom;
    protected Schedule $scheduleTo;

    protected function setUp(): void
    {
        parent::setUp();

        // Buat user dasar
        $this->user = User::factory()->create();

        // Buat dua kantor
        $this->officeFrom = Office::factory()->create(['name' => 'Kantor Asal']);
        $this->officeTo = Office::factory()->create(['name' => 'Kantor Tujuan']);

        // Buat dua jadwal, masing-masing untuk satu kantor
        $this->scheduleFrom = Schedule::factory()->create([
            'office_id' => $this->officeFrom->id,
            'schedule_name' => 'Jadwal Kantor Asal',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
        ]);
        $this->scheduleTo = Schedule::factory()->create([
            'office_id' => $this->officeTo->id,
            'schedule_name' => 'Jadwal Kantor Tujuan',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ]);

        // Set jadwal user ke jadwal kantor asal
        $this->user->schedule_id = $this->scheduleFrom->id;
        $this->user->save();

        // Autentikasi user untuk request API
        Sanctum::actingAs($this->user);
    }

    private function createTransferRequest(Carbon $effectiveDate): TransferRequest
    {
        return TransferRequest::factory()->create([
            'user_id' => $this->user->id,
            'current_schedule_id' => $this->scheduleFrom->id,
            'target_schedule_id' => $this->scheduleTo->id,
            'effective_date' => $effectiveDate->toDateString(),
            'status' => 'approved',
            'request_date' => $effectiveDate->copy()->subDay()->toDateString(), // Sehari sebelumnya
        ]);
    }

    private function createAttendanceRecord(Schedule $schedule, Carbon $date, ?string $timeIn, ?string $timeOut, string $statusAttendance, ?int $originalScheduleId = null, ?int $relatedAttendanceId = null)
    {
        return Attendance::factory()->create([
            'user_id' => $this->user->id,
            'schedule_id' => $schedule->id,
            'original_schedule_id' => $originalScheduleId,
            'related_attendance_id' => $relatedAttendanceId,
            'date' => $date->toDateString(),
            'time_in' => $timeIn,
            'time_out' => $timeOut,
            'status_attendance' => $statusAttendance,
            // Tambahkan field lain yang diperlukan oleh factory atau model jika ada
        ]);
    }

    /**
     * Test: User belum melakukan absensi sama sekali pada hari transfer.
     */
    public function testTodayStatusOnTransferDayBeforeAnyAttendance()
    {
        $today = Carbon::today();
        $transferRequest = $this->createTransferRequest($today);

        $response = $this->getJson('/api/attendance/today-status');

        $response->assertStatus(200)
            ->assertJson([
                'has_checked_in' => false,
                'has_checked_out' => false,
                'attendance' => null,
                'transfer_details' => [
                    'id' => $transferRequest->id,
                    'from_office' => [
                        'name' => $this->officeFrom->name,
                    ],
                    'to_office' => [
                        'name' => $this->officeTo->name,
                    ],
                    'current_stage' => 'pending_source_action',
                    'source_attendance' => null,
                    'destination_attendance' => null,
                ],
                'current_schedule' => [
                    'id' => $this->scheduleFrom->id,
                    'office_id' => $this->officeFrom->id,
                    'office' => [
                        'name' => $this->officeFrom->name
                    ]
                ]
            ]);
    }

    /**
     * Test: User telah check-in di kantor asal pada hari transfer.
     */
    public function testTodayStatusOnTransferDayCheckedInAtSource()
    {
        $today = Carbon::today();
        $transferRequest = $this->createTransferRequest($today);

        // User checks in at the source location
        $checkinAtSource = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'schedule_id' => $this->scheduleFrom->id, // Source schedule
            'date' => $today->toDateString(),
            'time_in' => '08:00:00',
            'status_attendance' => 'present', // CHANGED to 'present' to match factory default and simplify
            'original_schedule_id' => null, // Not a transfer out yet
        ]);

        $response = $this->getJson('/api/attendance/today-status');

        $response->assertStatus(200)
            ->assertJson([
                'has_checked_in' => true,
                'has_checked_out' => false,
                'transfer_details' => [
                    'id' => $transferRequest->id,
                    'current_stage' => 'pending_source_action', 
                    'source_attendance' => null, 
                    'destination_attendance' => null,
                ],
                'attendance' => [
                    'id' => $checkinAtSource->id,
                    'schedule_id' => $this->scheduleFrom->id,
                    'status_attendance' => 'present', // CHANGED to 'present'
                    'time_in' => '08:00:00'
                ],
                'current_schedule' => [
                    'id' => $this->scheduleFrom->id,
                ]
            ]);
    }

    /**
     * Test: User telah check-out dari kantor asal (transfer_out) pada hari transfer.
     */
    public function testTodayStatusOnTransferDayCheckedOutFromSource()
    {
        $today = Carbon::today();
        $transferRequest = $this->createTransferRequest($today);

        // User has an initial check-in and then a transfer_out from source
        $checkoutFromSource = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'schedule_id' => $transferRequest->current_schedule_id, // Ensure context is clear, though not directly used in transfer_out query
            'original_schedule_id' => $transferRequest->current_schedule_id, // Use directly from $transferRequest
            'date' => $today->toDateString(),
            'time_in' => '08:00:00', // Assuming they checked in earlier
            'time_out' => '12:00:00', // Transfer checkout time
            'status_attendance' => 'transfer_out',
        ]);

        $response = $this->getJson('/api/attendance/today-status');
        
        $response->assertStatus(200)
            ->assertJson([
                'has_checked_in' => true,
                'has_checked_out' => true,
                'attendance' => [
                    'id' => $checkoutFromSource->id,
                    'status_attendance' => 'transfer_out',
                    'time_out' => '12:00:00'
                ],
                'transfer_details' => [
                    'id' => $transferRequest->id,
                    'current_stage' => 'checked_out_from_source',
                    'source_attendance' => [
                        'id' => $checkoutFromSource->id,
                        'time_out' => '12:00:00'
                    ],
                    'destination_attendance' => null,
                ],
                'current_schedule' => [
                    'id' => $this->scheduleTo->id,
                    'office_id' => $this->officeTo->id
                ]
            ]);
    }

    /**
     * Test: User telah check-in di kantor tujuan setelah transfer_out.
     */
    public function testTodayStatusOnTransferDayCheckedInAtDestination()
    {
        $today = Carbon::today();
        $transferRequest = $this->createTransferRequest($today);

        $checkoutFromSource = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'schedule_id' => $transferRequest->current_schedule_id, // Context for source checkout
            'original_schedule_id' => $transferRequest->current_schedule_id,
            'date' => $today->toDateString(),
            'time_in' => '08:00:00',
            'time_out' => '12:00:00',
            'status_attendance' => 'transfer_out',
        ]);

        $checkinAtDestination = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'schedule_id' => $transferRequest->target_schedule_id, // Use directly from $transferRequest
            'original_schedule_id' => $transferRequest->current_schedule_id, // Link to original schedule of transfer
            'related_attendance_id' => $checkoutFromSource->id, // Link to the transfer_out record
            'date' => $today->toDateString(),
            'time_in' => '13:00:00',
            'status_attendance' => 'transfer_in',
        ]);

        $response = $this->getJson('/api/attendance/today-status');

        $response->assertStatus(200)
            ->assertJson([
                'has_checked_in' => true,
                'has_checked_out' => false,
                'transfer_details' => [
                    'id' => $transferRequest->id,
                    'current_stage' => 'completed',
                    'source_attendance' => [
                        'id' => $checkoutFromSource->id,
                    ],
                    'destination_attendance' => [
                        'id' => $checkinAtDestination->id,
                        'time_in' => '13:00:00'
                    ]
                ],
                'attendance' => [
                    'id' => $checkinAtDestination->id,
                    'schedule_id' => $this->scheduleTo->id,
                    'status_attendance' => 'transfer_in',
                    'time_in' => '13:00:00'
                ],
                'current_schedule' => [
                    'id' => $this->scheduleTo->id,
                ]
            ]);
    }

    /**
     * Test: User telah menyelesaikan semua absensi (check-out dari kantor tujuan) pada hari transfer.
     */
    public function testTodayStatusOnTransferDayAllCompleted()
    {
        $today = Carbon::today();
        $transferRequest = $this->createTransferRequest($today);

        $checkoutFromSource = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'schedule_id' => $transferRequest->current_schedule_id, // Context
            'original_schedule_id' => $transferRequest->current_schedule_id,
            'date' => $today->toDateString(),
            'time_in' => '08:00:00',
            'time_out' => '12:00:00',
            'status_attendance' => 'transfer_out',
        ]);

        $fullTransferAttendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'schedule_id' => $transferRequest->target_schedule_id, // Use directly
            'original_schedule_id' => $transferRequest->current_schedule_id,
            'related_attendance_id' => $checkoutFromSource->id,
            'date' => $today->toDateString(),
            'time_in' => '13:00:00',
            'time_out' => '17:00:00', // Checked out from destination
            'status_attendance' => 'transfer_in', // Status is still transfer_in, but time_out is filled
        ]);
        
        // The API should ideally handle the main 'attendance' record's status_attendance update upon clock-out.
        // For this test, we ensure the created record has time_out, and the controller's logic determines overall status.

        $response = $this->getJson('/api/attendance/today-status');

        $response->assertStatus(200)
            ->assertJson([
                'has_checked_in' => true,
                'has_checked_out' => true,
                'transfer_details' => [
                    'id' => $transferRequest->id,
                    'current_stage' => 'completed',
                    'source_attendance' => [
                        'id' => $checkoutFromSource->id,
                    ],
                    'destination_attendance' => [
                        'id' => $fullTransferAttendance->id,
                        'time_out' => '17:00:00'
                    ]
                ],
                'attendance' => [
                    'id' => $fullTransferAttendance->id,
                    'schedule_id' => $this->scheduleTo->id,
                    'time_out' => '17:00:00'
                ],
                'current_schedule' => [
                    'id' => $this->scheduleTo->id,
                ]
            ]);
    }

    /**
     * Test: User tidak ada transfer, absensi normal check-in.
     */
    public function testTodayStatusNoTransferNormalCheckIn()
    {
        $today = Carbon::today();
        $normalCheckin = $this->createAttendanceRecord($this->scheduleFrom, $today, '08:30:00', null, 'present');

        $response = $this->getJson('/api/attendance/today-status');

        $response->assertStatus(200)
            ->assertJson([
                'has_checked_in' => true,
                'has_checked_out' => false,
                'attendance' => [
                    'id' => $normalCheckin->id,
                    'status_attendance' => 'present',
                    'time_in' => '08:30:00'
                ],
                'transfer_details' => null,
                'current_schedule' => [
                    'id' => $this->scheduleFrom->id,
                ]
            ]);
    }

    /**
     * Test: User tidak ada transfer, absensi normal check-out.
     */
    public function testTodayStatusNoTransferNormalCheckOut()
    {
        $today = Carbon::today();
        $normalCheckout = $this->createAttendanceRecord($this->scheduleFrom, $today, '08:30:00', '17:30:00', 'checked_out');

        $response = $this->getJson('/api/attendance/today-status');

        $response->assertStatus(200)
            ->assertJson([
                'has_checked_in' => true,
                'has_checked_out' => true,
                'attendance' => [
                    'id' => $normalCheckout->id,
                    'status_attendance' => 'checked_out',
                    'time_out' => '17:30:00'
                ],
                'transfer_details' => null,
                'current_schedule' => [
                    'id' => $this->scheduleFrom->id,
                ]
            ]);
    }
} 
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TransferRequest;
use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TransferRequestController extends Controller
{
    /**
     * List transfer requests for authenticated user.
     */
    public function index(Request $request)
    {
        $requests = TransferRequest::with([
            'currentSchedule.office',
            'targetSchedule.office',
            'approver',
        ])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $requests], 200);
    }

    /**
     * Store a new transfer request.
     */
    public function store(Request $request)
    {
        $request->validate([
            'target_schedule_id' => 'required|exists:schedules,id',
            'reason' => 'nullable|string',
            'effective_date' => 'required|date|after_or_equal:today',
        ]);

        $user = $request->user();
        $transfer = TransferRequest::create([
            'user_id' => $user->id,
            'current_schedule_id' => $user->schedule_id,
            'target_schedule_id' => $request->target_schedule_id,
            'reason' => $request->reason,
            'effective_date' => $request->effective_date,
            'request_date' => now()->toDateString(),
            'status' => 'pending',
        ]);

        return response()->json(['message' => 'Permintaan transfer berhasil dibuat', 'data' => $transfer], 201);
    }
    
    /**
     * Get detailed information about a transfer, including related attendance records.
     */
    public function getTransferDetails($id)
    {
        $user = Auth::user();
        
        $transfer = TransferRequest::with([
            'currentSchedule.office',
            'targetSchedule.office',
            'approver',
        ])
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->first();
            
        if (!$transfer) {
            return response()->json(['message' => 'Transfer request tidak ditemukan'], 404);
        }
        
        // Get attendance records for the transfer date
        $attendances = Attendance::where('user_id', $user->id)
            ->whereDate('date', $transfer->effective_date)
            ->orderBy('created_at', 'asc')
            ->get();
            
        // Parse attendance records into transfer stages
        $initialCheckIn = $attendances->first(function ($attendance) {
            return in_array($attendance->status_attendance, ['present', 'checked_in']);
        });
        
        $transferOut = $attendances->first(function ($attendance) {
            return in_array($attendance->status_attendance, ['transfer_out', 'rolling_out']);
        });
        
        $transferIn = $attendances->first(function ($attendance) {
            return $attendance->status_attendance === 'rolling_in';
        });
        
        // Determine current stage
        $transferStage = 'not_started';
        if ($initialCheckIn && !$transferOut) {
            $transferStage = 'checked_in_original';
        } elseif ($transferOut && !$transferIn) {
            $transferStage = 'transfer_out_completed';
        } elseif ($transferIn && !$transferIn->time_out) {
            $transferStage = 'transfer_in_completed';
        } elseif ($transferIn && $transferIn->time_out) {
            $transferStage = 'all_completed';
        }
        
        $result = [
            'transfer_request' => $transfer,
            'stage' => $transferStage,
            'attendance_records' => [
                'initial_check_in' => $initialCheckIn,
                'transfer_out' => $transferOut,
                'transfer_in' => $transferIn,
            ],
            'from_office' => optional(optional($transfer->currentSchedule)->office)->name,
            'to_office' => optional(optional($transfer->targetSchedule)->office)->name,
        ];
        
        return response()->json($result);
    }
    
    /**
     * Get transfer history for a user.
     */
    public function getTransferHistory(Request $request)
    {
        $user = Auth::user();
        
        // Add logging flag
        $debugMode = $request->has('debug') && $request->debug === 'true';
        
        $query = TransferRequest::with([
            'currentSchedule.office',
            'targetSchedule.office',
            'approver',
        ])
            ->where('user_id', $user->id);
            
        // Optional date filtering
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('effective_date', [$request->start_date, $request->end_date]);
        } else if ($request->has('start_date')) {
            $query->where('effective_date', '>=', $request->start_date);
        } else if ($request->has('end_date')) {
            $query->where('effective_date', '<=', $request->end_date);
        }
        
        // Optional status filtering
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        $transfers = $query->orderBy('effective_date', 'desc')->get();
        
        // Transform data to include more readable information
        $transformedTransfers = $transfers->map(function ($transfer) use ($request, $user, $debugMode) {
            $isComplete = false;
            $sourceCheckIn = null;
            $sourceCheckOut = null;
            $destinationCheckIn = null;
            $destinationCheckOut = null;
            $sourceLatlonIn = null;
            $sourceLatlonOut = null;
            $destinationLatlonIn = null;
            $destinationLatlonOut = null;
            
            // Check if attendance data should be included
            $includeAttendance = $request->has('include_attendance') && $request->include_attendance === 'true';
            
            // Check if transfer is complete by looking for attendance records
            if ($transfer->status === 'approved' && Carbon::parse($transfer->effective_date)->lte(Carbon::today())) {
                // First, try to find the transfer-specific attendance record
                $attendance = Attendance::where('user_id', $user->id)
                    ->whereDate('date', $transfer->effective_date)
                    ->where('is_transfer_day', true)
                    ->where('transfer_request_id', $transfer->id)
                    ->first();
                
                if ($debugMode) {
                    \Log::info("Transfer #{$transfer->id} - Single attendance record:", [
                        'found' => $attendance ? 'yes' : 'no',
                        'attendance_id' => $attendance ? $attendance->id : null,
                        'source_time_in' => $attendance ? $attendance->source_time_in : null,
                        'source_time_out' => $attendance ? $attendance->source_time_out : null,
                        'destination_time_in' => $attendance ? $attendance->destination_time_in : null,
                        'destination_time_out' => $attendance ? $attendance->destination_time_out : null
                    ]);
                }
                
                if ($attendance) {
                    $isComplete = $attendance->transfer_status === 'completed';
                    
                    // If we need to include attendance data and the new format is available
                    if ($includeAttendance) {
                        // Source office attendance
                        $sourceCheckIn = $attendance->source_time_in ? Carbon::parse($attendance->source_time_in)->format('H:i:s') : null;
                        $sourceCheckOut = $attendance->source_time_out ? Carbon::parse($attendance->source_time_out)->format('H:i:s') : null;
                        $sourceLatlonIn = $attendance->source_latlon_in;
                        $sourceLatlonOut = $attendance->source_latlon_out;
                        
                        // Destination office attendance
                        $destinationCheckIn = $attendance->destination_time_in ? Carbon::parse($attendance->destination_time_in)->format('H:i:s') : null;
                        $destinationCheckOut = $attendance->destination_time_out ? Carbon::parse($attendance->destination_time_out)->format('H:i:s') : null;
                        $destinationLatlonIn = $attendance->destination_latlon_in;
                        $destinationLatlonOut = $attendance->destination_latlon_out;
                    }
                }
                // Fallback to the older multi-record format if needed
                else if ($includeAttendance) {
                    // Get all attendance records for the day
                    $attendances = Attendance::where('user_id', $user->id)
                        ->whereDate('date', $transfer->effective_date)
                        ->orderBy('created_at', 'asc')
                        ->get();
                    
                    if ($debugMode) {
                        \Log::info("Transfer #{$transfer->id} - Multi record format:", [
                            'total_records' => $attendances->count(),
                            'records' => $attendances->map(function($a) {
                                return [
                                    'id' => $a->id,
                                    'schedule_id' => $a->schedule_id,
                                    'time_in' => $a->time_in,
                                    'time_out' => $a->time_out,
                                    'status' => $a->status_attendance,
                                    'created_at' => $a->created_at,
                                ];
                            })
                        ]);
                    }
                    
                    // Count to determine if transfer was completed
                    $isComplete = $attendances->count() >= 2;
                    
                    // Try to find source and destination attendance by office or schedule
                    $sourceAttendance = $attendances->first(function ($record) use ($transfer) {
                        return $record->schedule_id == $transfer->current_schedule_id || 
                               ($record->status_attendance && in_array($record->status_attendance, ['present', 'checked_in', 'checked_in_source_transfer']));
                    });
                    
                    $transferOutRecord = $attendances->first(function ($record) {
                        return $record->status_attendance && $record->status_attendance == 'transfer_out';
                    });
                    
                    $destinationAttendance = $attendances->first(function ($record) use ($transfer) {
                        return $record->schedule_id == $transfer->target_schedule_id || 
                               ($record->status_attendance && $record->status_attendance == 'transfer_in');
                    });
                    
                    if ($debugMode) {
                        \Log::info("Transfer #{$transfer->id} - Identified records:", [
                            'source' => $sourceAttendance ? [
                                'id' => $sourceAttendance->id,
                                'time_in' => $sourceAttendance->time_in,
                                'time_out' => $sourceAttendance->time_out,
                                'status' => $sourceAttendance->status_attendance,
                            ] : null,
                            'transfer_out' => $transferOutRecord ? [
                                'id' => $transferOutRecord->id,
                                'time_out' => $transferOutRecord->time_out,
                                'status' => $transferOutRecord->status_attendance,
                            ] : null,
                            'destination' => $destinationAttendance ? [
                                'id' => $destinationAttendance->id,
                                'time_in' => $destinationAttendance->time_in,
                                'time_out' => $destinationAttendance->time_out,
                                'status' => $destinationAttendance->status_attendance,
                            ] : null,
                        ]);
                    }
                    
                    // Set times from found records
                    if ($sourceAttendance) {
                        $sourceCheckIn = $sourceAttendance->time_in ? Carbon::parse($sourceAttendance->time_in)->format('H:i:s') : null;
                        
                        // FIX: Prioritize the sourceAttendance->time_out for source checkout time 
                        // instead of potentially using transferOutRecord which might have the same time as destination checkout
                        // Only use transferOutRecord if sourceAttendance->time_out is empty
                        $sourceCheckOut = $sourceAttendance->time_out ? Carbon::parse($sourceAttendance->time_out)->format('H:i:s') : 
                                          ($transferOutRecord ? Carbon::parse($transferOutRecord->time_out)->format('H:i:s') : null);
                        
                        $sourceLatlonIn = $sourceAttendance->latlon_in;
                        $sourceLatlonOut = $sourceAttendance->latlon_out ?? ($transferOutRecord ? $transferOutRecord->latlon_out : null);
                    }
                    
                    if ($destinationAttendance) {
                        $destinationCheckIn = $destinationAttendance->time_in ? Carbon::parse($destinationAttendance->time_in)->format('H:i:s') : null;
                        $destinationCheckOut = $destinationAttendance->time_out ? Carbon::parse($destinationAttendance->time_out)->format('H:i:s') : null;
                        $destinationLatlonIn = $destinationAttendance->latlon_in;
                        $destinationLatlonOut = $destinationAttendance->latlon_out;
                    }
                }
            }
            
            $result = [
                'id' => $transfer->id,
                'from_office' => optional(optional($transfer->currentSchedule)->office)->name,
                'to_office' => optional(optional($transfer->targetSchedule)->office)->name,
                'effective_date' => $transfer->effective_date,
                'status' => $transfer->status,
                'approver_name' => optional($transfer->approver)->name,
                'approval_date' => $transfer->approval_date,
                'is_complete' => $isComplete,
            ];
            
            // Only include attendance data if requested
            if ($includeAttendance) {
                $result = array_merge($result, [
                    'source_check_in' => $sourceCheckIn,
                    'source_check_out' => $sourceCheckOut,
                    'destination_check_in' => $destinationCheckIn,
                    'destination_check_out' => $destinationCheckOut,
                    'source_latlon_in' => $sourceLatlonIn,
                    'source_latlon_out' => $sourceLatlonOut,
                    'destination_latlon_in' => $destinationLatlonIn,
                    'destination_latlon_out' => $destinationLatlonOut,
                ]);
            }
            
            if ($debugMode) {
                \Log::info("Transfer #{$transfer->id} - Final data being sent:", [
                    'source_check_in' => $sourceCheckIn,
                    'source_check_out' => $sourceCheckOut,
                    'destination_check_in' => $destinationCheckIn,
                    'destination_check_out' => $destinationCheckOut,
                ]);
            }
            
            return $result;
        });
        
        return response()->json(['data' => $transformedTransfers]);
    }
} 
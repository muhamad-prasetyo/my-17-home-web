<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Support\Facades\Storage;

class LeaveRequestController extends Controller
{
    /**
     * List leave requests for authenticated user.
     */
    public function index(Request $request)
    {
        $leaves = LeaveRequest::with('leaveType')
            ->where('user_id', $request->user()->id)
            ->get();

        return response()->json(['data' => $leaves], 200);
    }

    /**
     * Store a new leave request.
     */
    public function store(Request $request)
    {
        $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string',
            'attachment' => 'file|nullable|max:2048',
        ]);

        $leave = new LeaveRequest();
        $leave->user_id = $request->user()->id;
        $leave->leave_type_id = $request->leave_type_id;
        $leave->start_date = $request->start_date;
        $leave->end_date = $request->end_date;
        $leave->reason = $request->reason;
        $leave->status = 'pending';

        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('leave-attachments', 'public');
            $leave->attachment_path = $path;
        }

        $leave->save();

        return response()->json(['message' => 'Leave request created', 'data' => $leave], 201);
    }
} 
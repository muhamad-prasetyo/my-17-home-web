<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Schedule;

class ScheduleController extends Controller
{
    /**
     * List all schedules with office relation.
     */
    public function index(Request $request)
    {
        $schedules = Schedule::with('office')->get();
        return response()->json(['data' => $schedules], 200);
    }
} 
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveType;

class LeaveTypeController extends Controller
{
    public function index()
    {
        return response()->json(LeaveType::all());
    }
} 
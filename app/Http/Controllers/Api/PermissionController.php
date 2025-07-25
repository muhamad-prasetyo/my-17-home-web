<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Permission;
use App\Events\PermissionRequestSent;

class PermissionController extends Controller
{
    /**
     * List user permissions
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $permissions = Permission::where('user_id', $user->id)
            ->orderBy('date_permission', 'desc')
            ->get();
        return response()->json(['data' => $permissions], 200);
    }

    /**
     * Show permission detail
     */
    public function show(Request $request, $id)
    {
        $permission = Permission::where('user_id', $request->user()->id)
            ->findOrFail($id);
        return response()->json(['data' => $permission], 200);
    }

    //create
    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required',
            'reason' => 'required',
        ]);

        $permission = new Permission();
        $permission->user_id = $request->user()->id;
        $permission->date_permission = $request->date;
        $permission->reason = $request->reason;
        $permission->is_approved = 0;

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $path = $image->store('permissions_proof', 'public');
            $permission->image = $path;
        }

        $permission->save();

        // Dispatch the event
        $userName = $request->user()->name ?? 'Seorang pengguna'; // Get user name, fallback if null
        $requestType = $request->reason ?? 'Tidak disebutkan'; // Use reason as request type, fallback if null
        $message = "Permintaan izin baru dari {$userName}.";
        PermissionRequestSent::dispatch($message, $userName, $requestType, $permission->id);

        return response()->json(['message' => 'Permission created successfully'], 201);
    }
}

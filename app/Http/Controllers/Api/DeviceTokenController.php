<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserDeviceToken;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    /**
     * List all device tokens for the authenticated user.
     */
    public function index(Request $request)
    {
        $tokens = $request->user()->deviceTokens()->get();
        return response()->json($tokens);
    }

    /**
     * Store or update a device token for the authenticated user.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'device_token'  => 'required|string',
            'device_type'   => 'required|in:android,ios,web',
            'device_name'   => 'nullable|string',
            'last_location' => 'nullable|string',
          ]);
          $token = $request->user()
            ->deviceTokens()
            ->updateOrCreate(
              ['device_token' => $data['device_token']],
              array_merge($data, ['last_used_at' => now()])
            );

        return response()->json($token, 201);
    }

    /**
     * Delete a device token.
     */
    public function destroy(Request $request, $id)
    {
        $token = $request->user()->deviceTokens()->findOrFail($id);
        $token->delete();
        return response()->json(null, 204);
    }
} 
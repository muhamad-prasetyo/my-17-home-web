<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Notifications\UserRegisteredNotification;

class AuthController extends Controller
{
    // REGISTER DENGAN APPROVAL
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'phone' => 'required',
            'password' => 'required|min:6|confirmed',
        ]);
        $user = \App\Models\User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => bcrypt($request->password),
            'is_approved' => false, // <--- Tambahan approval
        ]);
        // Kirim notifikasi ke admin (super_admin/hrd)
        $adminUsers = collect();
        try {
            if (\Spatie\Permission\Models\Role::where('name', 'super_admin')->where('guard_name', 'web')->exists()) {
                $adminUsers = $adminUsers->merge(\App\Models\User::role('super_admin')->get());
            }
            if (\Spatie\Permission\Models\Role::where('name', 'hrd')->where('guard_name', 'web')->exists()) {
                $adminUsers = $adminUsers->merge(\App\Models\User::role('hrd')->get());
            }
        } catch (\Throwable $e) {
            \Log::error('ERROR get admin users for register notification: ' . $e->getMessage());
        }
        $adminUsers = $adminUsers->unique('id');
        \Log::info('DEBUG REGISTER: Akan kirim notifikasi ke admin', ['admin_ids' => $adminUsers->pluck('id')->toArray()]);
        foreach ($adminUsers as $admin) {
            try {
                $admin->notify(new UserRegisteredNotification($user));
                \Log::info('DEBUG REGISTER: Notifikasi berhasil dikirim ke admin', ['admin_id' => $admin->id]);
            } catch (\Throwable $e) {
                \Log::error('ERROR notify admin on register: ' . $e->getMessage());
            }
        }
        return response(['message' => 'Registrasi berhasil, menunggu persetujuan admin/HRD.'], 201);
    }

    // LOGIN DENGAN CEK APPROVAL
    public function login(Request $request)
    {
        $loginData = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = \App\Models\User::where('email', $loginData['email'])->first();

        if (!$user) {
            return response(['message' => 'Invalid credentials'], 401);
        }

        if (!\Illuminate\Support\Facades\Hash::check($loginData['password'], $user->password)) {
            return response(['message' => 'Invalid credentials'], 401);
        }

        if (!$user->is_approved) {
            return response(['message' => 'Akun Anda belum disetujui oleh admin/HRD.'], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response(['user' => $user, 'token' => $token], 200);
    }

    //logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response(['message' => 'Logged out'], 200);
    }

    //update image profile & face_embedding
    public function updateProfile(Request $request)
    {
        $request->validate([
            // 'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'face_embedding' => 'required',
        ]);

        $user = $request->user();
        // $image = $request->file('image');
        $face_embedding = $request->face_embedding;

        // //save image
        // $image->storeAs('public/images', $image->hashName());
        // $user->image_url = $image->hashName();
        
        // Pastikan face_embedding tersimpan dengan benar
        $user->face_embedding = $face_embedding;
        // Jika face_registered_at masih null, isi dengan tanggal hari ini
        if (is_null($user->face_registered_at)) {
            $user->face_registered_at = now()->toDateString();
        }
        $user->save();
        
        // Verifikasi data tersimpan dengan benar
        $freshUser = User::find($user->id);
        if ($freshUser->face_embedding !== $face_embedding) {
            \Log::error('Face embedding tidak tersimpan dengan benar. User ID: ' . $user->id);
        } else {
            \Log::info('Face embedding berhasil tersimpan untuk user ID: ' . $user->id . ', length: ' . strlen($face_embedding));
        }

        // Pastikan face_embedding dikembalikan dalam respons
        return response([
            'message' => 'Profile updated',
            'user' => $freshUser->makeVisible('face_embedding'),
        ], 200);
    }

    //update fcm token
    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required',
        ]);

        $user = $request->user();
        $user->fcm_token = $request->fcm_token;
        $user->save();

        return response([
            'message' => 'FCM token updated',
        ], 200);
    }

    /**
     * Update user password
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6|regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x]).*$/',
            'new_password_confirmation' => 'required|same:new_password',
        ], [
            'new_password.regex' => 'Password harus mengandung minimal satu huruf, satu angka, dan satu karakter khusus.'
        ]);

        $user = $request->user();

        // Check if current password matches
        if (!Hash::check($request->current_password, $user->password)) {
            return response([
                'message' => 'Kata sandi saat ini tidak cocok.'
            ], 422);
        }

        // Update password
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response([
            'message' => 'Kata sandi berhasil diperbarui.'
        ], 200);
    }
}

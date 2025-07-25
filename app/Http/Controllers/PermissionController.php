<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Permission;
use App\Models\User;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Resend\Laravel\Facades\Resend;
use App\Mail\ApprovedPermissionConfirmation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;



class PermissionController extends Controller
{
    //index
    public function index(Request $request)
    {
        $permissions = Permission::with('user')
            ->when($request->input('name'), function ($query, $name) {
                $query->whereHas('user', function ($query) use ($name) {
                    $query->where('name', 'like', '%' . $name . '%');
                });
            })->orderBy('id', 'desc')->paginate(10);
        return view('pages.permission.index', compact('permissions'));
    }

    //view
    public function show($id)
    {
        $permission = Permission::with('user')->find($id);
        return view('pages.permission.show', compact('permission'));
    }

    //edit
    public function edit($id)
    {
        $permission = Permission::find($id);
        return view('pages.permission.edit', compact('permission'));
    }

    //update
    public function update(Request $request, $id)
    {
        \Log::info('PermissionController@update: Method CALLED for ID ' . $id);

        $permission = Permission::find($id);
        if (!$permission) {
            \Log::error('PermissionController@update: Permission not found for ID ' . $id);
            return redirect()->route('permissions.index')->with('error', 'Permission not found.');
        }

        $permission->is_approved = $request->is_approved;
        $str = $request->is_approved == 1 ? 'Disetujui' : 'Ditolak';
        $permission->save();

        $user = User::find($permission->user_id);
        if (!$user) {
            \Log::error('PermissionController@update: User not found for permission ID ' . $id . ', user ID ' . $permission->user_id);
        }

        $permission_date = $permission->date_permission;
        $date = Carbon::parse($permission_date)->translatedFormat('d F Y');
        $reason = $permission->reason;
        \Log::info('PermissionController@update: Attempting to send notification for user ID ' . $permission->user_id);
        $this->sendNotificationToUser($permission->user_id, 'Status Izin anda adalah ' . $str);
        
        if ($request->is_approved == 1 && $user) {
            //sent email with Mail
            Mail::to($user->email)->send(new ApprovedPermissionConfirmation($user, $date, $reason));
            \Log::info('PermissionController@update: Approval email sent to ' . $user->email);
        }
        return redirect()->route('permissions.index')->with('success', 'Permission updated successfully');
    }

    public function sendNotificationToUser($userId, $message)
    {
        \Log::info('PermissionController@sendNotificationToUser: Method CALLED for user ID ' . $userId . ' with message: ' . $message);

        // Dapatkan FCM token user dari tabel 'users'

        $user = User::find($userId);
        if (!$user) {
            \Log::error('FCM failed: User not found for ID ' . $userId);
            return;
        }

        $token = $user->fcm_token;

        if (empty($token)) {
            \Log::warning('FCM not sent: Token is empty for user ID ' . $userId);
            return;
        }

        // Kirim notifikasi ke perangkat Android
        \Log::info('Attempting to send FCM to user ' . $userId . ' with token ' . $token . ' and message: ' . $message);
        $messaging = app('firebase.messaging');
        $notification = Notification::create('Status Izin', $message);

        $cloudMessage = CloudMessage::withTarget('token', $token)
            ->withNotification($notification);

        try {
            $messaging->send($cloudMessage);
            \Log::info('FCM sent successfully to user ' . $userId);
        } catch (\Kreait\Firebase\Exception\MessagingException $e) {
            \Log::error('FCM failed for user ' . $userId . ' with token ' . $token . '. Error: ' . $e->getMessage());
            // Anda mungkin ingin memeriksa apakah token tidak valid dan menghapusnya dari database
            // if ($e->getCode() === SOME_ERROR_CODE_FOR_INVALID_TOKEN) {
            //     $user->fcm_token = null;
            //     $user->save();
            //     \Log::info('Removed invalid FCM token for user ' . $userId);
            // }
        } catch (\Exception $e) {
            \Log::error('General error sending FCM for user ' . $userId . ' with token ' . $token . '. Error: ' . $e->getMessage());
        }
    }
}

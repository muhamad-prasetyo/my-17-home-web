<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    //index
    public function index()
    {
        //search by name, pagination 10
        $users = User::where('name', 'like', '%' . request('name') . '%')
            ->orderBy('id', 'desc')
            ->paginate(10);
        return view('pages.users.index', compact('users'));
    }

    //create
    public function create()
    {
        return view('pages.users.create');
    }

    //store
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role' => $request->role,
            'password' => Hash::make($request->password),
            'position' => $request->position,
            'department' => $request->department,
            'tanggal_lahir' => $request->tanggal_lahir,
            'kewarganegaraan' => $request->kewarganegaraan,
            'agama' => $request->agama,
            'jenis_kelamin' => $request->jenis_kelamin,
            'status_pernikahan' => $request->status_pernikahan,
            'waktu_kontrak' => $request->waktu_kontrak,
            'tinggi_badan' => $request->tinggi_badan,
            'berat_badan' => $request->berat_badan,
            'golongan_darah' => $request->golongan_darah,
            'gangguan_penglihatan' => $request->gangguan_penglihatan,
            'buta_warna' => $request->buta_warna,
        ]);

        return redirect()->route('users.index')->with('success', 'User created successfully');
    }

    //edit
    public function edit(User $user)
    {
        return view('pages.users.edit', compact('user'));
    }

    //update
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role' => $request->role,
            'position' => $request->position,
            'department' => $request->department,
            'tanggal_lahir' => $request->tanggal_lahir,
            'kewarganegaraan' => $request->kewarganegaraan,
            'agama' => $request->agama,
            'jenis_kelamin' => $request->jenis_kelamin,
            'status_pernikahan' => $request->status_pernikahan,
            'waktu_kontrak' => $request->waktu_kontrak,
            'tinggi_badan' => $request->tinggi_badan,
            'berat_badan' => $request->berat_badan,
            'golongan_darah' => $request->golongan_darah,
            'gangguan_penglihatan' => $request->gangguan_penglihatan,
            'buta_warna' => $request->buta_warna,
        ]);

        //if password filled
        if ($request->password) {
            $user->update([
                'password' => Hash::make($request->password),
            ]);
        }

        return redirect()->route('users.index')->with('success', 'User updated successfully');
    }

    //destroy
    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('users.index')->with('success', 'User deleted successfully');
    }
}

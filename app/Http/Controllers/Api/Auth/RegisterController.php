<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function handle(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required'],
            'device_name' => ['required'],
            'name' => ['required', 'min:3']
        ]);

        $user = User::create([
            'name' => $request->name,
            'password' => bcrypt($request->password),
            'email' => $request->email,
            'role' => 0
        ]);

        return [
            'token' => $user->createToken($request->device_name)->plainTextToken,
            'role' => $user->role,
            'status' => 200
        ];

    }
}

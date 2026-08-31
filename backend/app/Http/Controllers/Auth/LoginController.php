<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->is_suspended) {
            return response()->json([
                'message' => 'Akun Anda sedang ditangguhkan (suspend). Silakan hubungi administrator.',
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login Berhasil',
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout Berhasil',
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function upgradeToCreator(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'creator') {
            return response()->json([
                'message' => 'Akun Anda sudah memiliki role creator.',
                'user' => $user,
            ]);
        }

        $user->update(['role' => 'creator']);

        return response()->json([
            'message' => 'Selamat! Akun Anda berhasil di-upgrade menjadi creator.',
            'user' => $user,
        ]);
    }
}

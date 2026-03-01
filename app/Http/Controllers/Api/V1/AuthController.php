<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->status !== 'active') {
            return $this->error('Your account is inactive. Please contact admin.', 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        $this->logActivity('auth.login', $user, null, ['email' => $user->email]);

        return $this->success([
            'user' => $user->load('roles.permissions'),
            'token' => $token,
        ], 'Login successful');
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();
        $this->logActivity('auth.logout', $user);
        return $this->success(null, 'Logged out successfully');
    }

    public function me(Request $request)
    {
        $user = $request->user()->load(['roles.permissions', 'directPermissions', 'employeeDetail']);
        return $this->success([
            'user' => $user,
            'permissions' => $user->getAllPermissions()
        ]);
    }
}

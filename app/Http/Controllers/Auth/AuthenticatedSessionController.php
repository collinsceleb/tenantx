<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request)
    {
        $request->authenticate();

        // Get the logged-in user
        $user = Auth::user();
        DB::table('user_logins')->updateOrInsert(
            ['user_id' => $user->id],
            [
                'user_agent' => $request->userAgent(),
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Create Sanctum token
        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function destroy(Request $request)
    {
        // Revoke current token
        $request->user()->tokens()->where('id', $request->user()->currentAccessToken()->id)->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}

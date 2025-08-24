<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Models\RefreshToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

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
        DB::table('users_login')->updateOrInsert(
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
        $accessToken = $user->createToken('api_token', ['*'], now()->addMinutes(15))->plainTextToken;

        $plainRefreshToken = Str::random(64);
        RefreshToken::create([
            'user_id' => $user->id,
            'expires_at' => now()->addDays(7),
            'refresh_token' => hash('sha256', $plainRefreshToken),
        ]);

        return response()->json([
            'accessToken' => $accessToken,
            'refreshToken' => $plainRefreshToken,
            'token_type' => 'Bearer',
            'expires_in' => 900,
        ]);
    }

    public function destroy(Request $request)
    {
        // Revoke current token
        $request->user()->tokens()->where('id', $request->user()->currentAccessToken()->id)->delete();
        RefreshToken::where('user_id', $request->user()->id)->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function refresh(RefreshTokenRequest $request)
    {

        $refreshToken = RefreshToken::where('refresh_token', hash('sha256', $request->refresh_token))
            ->where('expires_at', '>', now())
            ->first();

        if (!$refreshToken) {
            return response()->json(['message' => 'Invalid or expired refresh token'], 401);
        }

        $user = $refreshToken->user;

        // Revoke the used refresh token
        $refreshToken->delete();

        // Create new Sanctum token
        $accessToken = $user->createToken('api_token', ['*'], now()->addMinutes(15))->plainTextToken;

        $plainRefreshToken = Str::random(64);
        // Create new refresh token
        RefreshToken::create([
            'user_id' => $user->id,
            'expires_at' => now()->addDays(7),
            'refresh_token' => hash('sha256', $plainRefreshToken),
        ]);

        return response()->json([
            'accessToken' => $accessToken,
            'refreshToken' => $plainRefreshToken,
            'token_type' => 'Bearer',
            'expires_in' => 900,
        ]);
    }
}

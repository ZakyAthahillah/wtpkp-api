<?php

namespace App\Http\Middleware;

use App\Helpers\ApiResponse;
use App\Helpers\JwtToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class JwtAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = JwtToken::parseBearer($request->header('Authorization'));

        if (! $token) {
            return ApiResponse::error('Unauthenticated', ['token' => ['Bearer token is required.']], 401);
        }

        $payload = JwtToken::decode($token);

        if (! $payload) {
            return ApiResponse::error('Unauthenticated', ['token' => ['Token is invalid, expired, or revoked.']], 401);
        }

        $user = DB::table('wtp_users')->where('user_id', (int) $payload['sub'])->first();

        if (! $user) {
            return ApiResponse::error('Unauthenticated', ['user' => ['Token user was not found.']], 401);
        }

        $request->attributes->set('jwt_payload', $payload);
        $request->attributes->set('auth_user', $user);
        $request->attributes->set('jwt_token', $token);

        return $next($request);
    }
}

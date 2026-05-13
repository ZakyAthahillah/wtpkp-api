<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Helpers\JwtToken;
use App\Helpers\PasswordVerifier;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        try {
            $payload = Validator::make($request->all(), [
                'username' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('wtp_users', 'username')],
                'password' => ['required', 'string', 'min:6', 'max:255'],
                'full_name' => ['required', 'string', 'max:255'],
                'email' => ['nullable', 'email', 'max:255', Rule::unique('wtp_users', 'email')],
                'role_id' => ['nullable', 'integer'],
            ])->validate();

            $user = DB::transaction(function () use ($payload) {
                $userId = DB::table('wtp_users')->insertGetId([
                    'username' => $payload['username'],
                    'email' => $payload['email'] ?? $payload['username'].'@wtpkp.local',
                    'full_name' => $payload['full_name'],
                    'role_id' => $payload['role_id'] ?? null,
                    'created_at' => now(),
                ], 'user_id');

                DB::table('user_auth')->insert([
                    'user_id' => $userId,
                    'password_hash' => Hash::make($payload['password']),
                ]);

                return DB::table('wtp_users')->where('user_id', $userId)->first();
            });

            return ApiResponse::success('Register successful', $user, null, 201);
        } catch (ValidationException $exception) {
            return ApiResponse::error('Bad request', $exception->errors(), 400);
        } catch (Throwable $exception) {
            report($exception);

            return ApiResponse::error('Failed to register');
        }
    }

    public function login(Request $request): JsonResponse
    {
        try {
            $payload = Validator::make($request->all(), [
                'username' => ['required', 'string', 'max:100'],
                'password' => ['required', 'string', 'max:255'],
            ])->validate();

            $user = DB::table('wtp_users')
                ->whereRaw('LOWER(username) = ?', [strtolower($payload['username'])])
                ->first();

            if (! $user) {
                return ApiResponse::error('Invalid login credentials', ['username' => ['Username or password is incorrect.']], 401);
            }

            $auth = DB::table('user_auth')->where('user_id', $user->user_id)->first();

            if (! $auth || ! PasswordVerifier::check($payload['password'], $auth)) {
                return ApiResponse::error('Invalid login credentials', ['username' => ['Username or password is incorrect.']], 401);
            }

            $token = JwtToken::issue((array) $user);

            return ApiResponse::success('Login successful', [
                'user' => $user,
                'token' => $token,
            ]);
        } catch (ValidationException $exception) {
            return ApiResponse::error('Bad request', $exception->errors(), 400);
        } catch (Throwable $exception) {
            report($exception);

            return ApiResponse::error('Failed to login');
        }
    }

    public function me(Request $request): JsonResponse
    {
        try {
            return ApiResponse::success('Data retrieved successfully', $request->attributes->get('auth_user'));
        } catch (Throwable $exception) {
            report($exception);

            return ApiResponse::error('Failed to retrieve user');
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            JwtToken::revoke($request->attributes->get('jwt_payload'));

            return ApiResponse::success('Logout successful');
        } catch (Throwable $exception) {
            report($exception);

            return ApiResponse::error('Failed to logout');
        }
    }
}

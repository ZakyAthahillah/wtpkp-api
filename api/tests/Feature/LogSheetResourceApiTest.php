<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LogSheetResourceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_returns_standard_response(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'API is healthy',
                'errors' => null,
            ]);
    }

    public function test_protected_endpoint_requires_jwt(): void
    {
        $this->getJson('/api/resources')
            ->assertStatus(401)
            ->assertJsonPath('errors.token.0', 'Bearer token is required.');
    }

    public function test_can_login_read_me_and_logout_with_jwt(): void
    {
        $this->createUser();

        $token = $this->postJson('/api/auth/login', [
            'username' => 'operator',
            'password' => 'secret-password',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Login successful')
            ->json('data.token.access_token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.username', 'operator')
            ->assertJsonPath('data.email', 'operator@example.com');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logout successful');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/me')
            ->assertStatus(401)
            ->assertJsonPath('errors.token.0', 'Token is invalid, expired, or revoked.');
    }

    public function test_login_returns_validation_error(): void
    {
        $this->postJson('/api/auth/login', [
            'username' => 'operator',
        ])
            ->assertStatus(400)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_can_register_with_username_and_password(): void
    {
        $this->postJson('/api/auth/register', [
            'username' => 'shift_leader',
            'password' => 'secret-password',
            'full_name' => 'Shift Leader',
            'email' => 'shift.leader@example.com',
        ])
            ->assertCreated()
            ->assertJsonPath('message', 'Register successful')
            ->assertJsonPath('data.username', 'shift_leader');

        $this->postJson('/api/auth/login', [
            'username' => 'shift_leader',
            'password' => 'secret-password',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Login successful');
    }

    public function test_can_create_and_show_wtp_log_sheet(): void
    {
        $this->createMasterLocation();

        $response = $this->withJwt()->postJson('/api/log-sheets/wtp', [
            'lokasi' => 'LOC001',
            'shift' => 'Shift 2',
            'ph' => 7.12,
            'cond' => 12.34,
            'sio2' => 10.2,
        ]);

        $id = $response
            ->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'Data created successfully',
                'data' => [
                    'shift' => 'Shift Siang',
                    'sio2' => 10.2,
                    'operator_name' => 'Operator A',
                ],
                'errors' => null,
            ])
            ->json('data.log_id');

        $this->withJwt()->getJson('/api/log-sheets/wtp/'.$id)
            ->assertOk()
            ->assertJsonPath('data.shift', 'Shift Siang')
            ->assertJsonPath('data.location_name', 'DM WATER')
            ->assertJsonPath('data.sio2', 10.2)
            ->assertJsonPath('data.lokasi', 'LOC001');
    }

    public function test_create_rejects_unknown_shift(): void
    {
        $this->createMasterLocation();

        $this->withJwt()->postJson('/api/log-sheets/wtp', [
            'lokasi' => 'LOC001',
            'shift' => 'Shift 4',
        ])
            ->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Bad request',
                'data' => null,
                'meta' => null,
            ])
            ->assertJsonValidationErrors(['shift']);
    }

    public function test_create_rejects_unknown_master_location(): void
    {
        $this->withJwt()->postJson('/api/log-sheets/wtp', [
            'lokasi' => 'LOC999',
            'shift' => 'Shift 1',
        ])
            ->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Bad request',
                'data' => null,
                'meta' => null,
            ])
            ->assertJsonValidationErrors(['lokasi']);
    }

    public function test_create_returns_validation_error(): void
    {
        $this->withJwt()->postJson('/api/log-sheets/wtp', [
            'ph' => 7.12,
        ])
            ->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Bad request',
                'data' => null,
                'meta' => null,
            ])
            ->assertJsonValidationErrors(['lokasi']);
    }

    public function test_list_endpoint_is_paginated_and_filterable(): void
    {
        $this->createMasterLocation();
        DB::table('log_sheet_wtp')->insert([
            ['lokasi' => 'LOC001', 'operator_name' => 'Operator A', 'ph' => 7.1],
            ['lokasi' => 'LOC001', 'operator_name' => 'Operator B', 'ph' => 7.2],
        ]);

        $this->withJwt()->getJson('/api/log-sheets/wtp?per_page=1&lokasi=LOC001')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.location_name', 'DM WATER')
            ->assertJsonCount(1, 'data');
    }

    public function test_update_requires_at_least_one_field(): void
    {
        $this->createMasterLocation();
        $id = DB::table('log_sheet_wtp')->insertGetId([
            'lokasi' => 'LOC001',
            'operator_name' => 'Operator A',
        ], 'log_id');

        $this->withJwt()->putJson('/api/log-sheets/wtp/'.$id, [])
            ->assertStatus(400)
            ->assertJsonPath('errors.request.0', 'At least one field must be provided for update.');
    }

    public function test_unsupported_resource_returns_bad_request(): void
    {
        $this->withJwt()->getJson('/api/log-sheets/not-real')
            ->assertStatus(400)
            ->assertJsonValidationErrors(['resource']);
    }

    public function test_can_delete_data(): void
    {
        $this->createMasterLocation();
        $id = DB::table('log_sheet_wtp')->insertGetId([
            'lokasi' => 'LOC001',
            'operator_name' => 'Operator A',
        ], 'log_id');

        $this->withJwt()->deleteJson('/api/log-sheets/wtp/'.$id)
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Data deleted successfully',
            ]);

        $this->assertDatabaseMissing('log_sheet_wtp', ['log_id' => $id]);
    }

    private function createMasterLocation(): void
    {
        DB::table('master_locations')->insert([
            'location_code' => 'LOC001',
            'location_name' => 'DM WATER',
        ]);
    }

    private function withJwt(): self
    {
        $this->createUser();

        $token = $this->postJson('/api/auth/login', [
            'username' => 'operator',
            'password' => 'secret-password',
        ])->json('data.token.access_token');

        return $this->withHeader('Authorization', 'Bearer '.$token);
    }

    private function createUser(): void
    {
        if (DB::table('wtp_users')->where('username', 'operator')->exists()) {
            return;
        }

        $userId = DB::table('wtp_users')->insertGetId([
            'username' => 'operator',
            'email' => 'operator@example.com',
            'full_name' => 'Operator A',
            'role_id' => null,
        ], 'user_id');

        DB::table('user_auth')->insert([
            'user_id' => $userId,
            'password_hash' => Hash::make('secret-password'),
        ]);
    }
}

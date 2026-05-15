<?php

namespace Tests\Feature;

use Carbon\CarbonImmutable;
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

    public function test_masters_endpoint_requires_jwt(): void
    {
        $this->getJson('/api/masters/locations')
            ->assertStatus(401)
            ->assertJsonPath('errors.token.0', 'Bearer token is required.');
    }

    public function test_summary_endpoint_requires_jwt(): void
    {
        $this->getJson('/api/summaries/wtp')
            ->assertStatus(401)
            ->assertJsonPath('errors.token.0', 'Bearer token is required.');
    }

    public function test_report_endpoint_requires_jwt(): void
    {
        $this->getJson('/api/reports/monthly-consumption?month=2026-05')
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

    public function test_can_get_master_locations(): void
    {
        $this->createMasterLocation();

        $this->withJwt()->getJson('/api/masters/locations')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Data retrieved successfully',
                'errors' => null,
            ])
            ->assertJsonPath('data.0.location_code', 'LOC001')
            ->assertJsonPath('meta', null);
    }

    public function test_can_get_master_standard_ro(): void
    {
        DB::table('master_standard_ro')->insert([
            'loc_id' => 'RO001',
            'location_name' => 'RO PLANT',
        ]);

        $this->withJwt()->getJson('/api/masters/standard-ro')
            ->assertOk()
            ->assertJsonPath('data.0.loc_id', 'RO001')
            ->assertJsonPath('data.0.location_name', 'RO PLANT');
    }

    public function test_can_get_master_standard_ro_process(): void
    {
        DB::table('master_standard_ro_process')->insert([
            'loc_id' => 'ROP001',
            'location_name' => 'RO PROCESS',
        ]);

        $this->withJwt()->getJson('/api/masters/standard-ro-process')
            ->assertOk()
            ->assertJsonPath('data.0.loc_id', 'ROP001')
            ->assertJsonPath('data.0.location_name', 'RO PROCESS');
    }

    public function test_can_get_wtp_summary(): void
    {
        $this->createMasterLocation();
        DB::table('log_sheet_wtp')->insert([
            [
                'log_date' => CarbonImmutable::today()->toDateTimeString(),
                'shift' => 'Shift Pagi',
                'lokasi' => 'LOC001',
                'ph' => 7.1,
                'cond' => 12.3,
                'tds' => 10.2,
                'p_alk' => 1.2,
                'm_alk' => 2.3,
                'th' => 3.4,
                'sio2' => 4.5,
                'operator_name' => 'Operator A',
            ],
        ]);

        $this->withJwt()->getJson('/api/summaries/wtp?lokasi=LOC001')
            ->assertOk()
            ->assertJsonPath('data.info.location_code', 'LOC001')
            ->assertJsonPath('data.info.location_name', 'DM WATER')
            ->assertJsonPath('data.rows.0.avg_ph', 7.1)
            ->assertJsonPath('data.cards.0.metric', 'ph')
            ->assertJsonPath('data.cards.0.value', 7.1);
    }

    public function test_wtp_summary_rejects_unknown_location(): void
    {
        $this->withJwt()->getJson('/api/summaries/wtp?lokasi=LOC999')
            ->assertStatus(400)
            ->assertJsonValidationErrors(['lokasi']);
    }

    public function test_can_get_ro_process_summary(): void
    {
        DB::table('master_standard_ro_process')->insert([
            'loc_id' => 'PROC01',
            'location_name' => 'SEA WATER',
            'ph_min' => 6.5,
            'ph_max' => 8.5,
            'cond_max' => 50,
        ]);
        DB::table('log_sheet_ro_process')->insert([
            'log_date' => CarbonImmutable::today()->toDateTimeString(),
            'shift' => 'Shift Pagi',
            'lokasi' => 'PROC01',
            'ph' => 7.4,
            'cond' => 22.5,
            'operator_name' => 'Operator A',
        ]);

        $this->withJwt()->getJson('/api/summaries/ro-process?param=Cond')
            ->assertOk()
            ->assertJsonPath('data.info.param', 'cond')
            ->assertJsonPath('data.rows.0.location_code', 'PROC01')
            ->assertJsonPath('data.rows.0.avg_cond', 22.5)
            ->assertJsonPath('data.cards.0.metric', 'cond');
    }

    public function test_ro_process_summary_rejects_unknown_param(): void
    {
        $this->withJwt()->getJson('/api/summaries/ro-process?param=TDS')
            ->assertStatus(400)
            ->assertJsonValidationErrors(['param']);
    }

    public function test_can_get_wwtp_ol_summary(): void
    {
        DB::table('log_sheet_wwtp_ol')->insert([
            'log_date' => CarbonImmutable::today()->toDateString(),
            'input_time' => CarbonImmutable::today()->toDateTimeString(),
            'operator_name' => 'Operator A',
            'unit_name' => 'LOC029',
            'cu' => 1.2,
            'zn' => 2.3,
            'cr' => 3.4,
            'tss' => 4.5,
            'fe' => 5.6,
            'po5' => 6.7,
        ]);

        $this->withJwt()->getJson('/api/summaries/wwtp-ol')
            ->assertOk()
            ->assertJsonPath('data.info.location_code', 'LOC029')
            ->assertJsonPath('data.rows.0.avg_cu', 1.2)
            ->assertJsonPath('data.cards.0.metric', 'cu')
            ->assertJsonPath('data.cards.0.value', 1.2);
    }

    public function test_can_get_monthly_consumption_report_excel_data(): void
    {
        DB::table('log_sheet_water_production')->insert([
            'log_date' => '2026-05-15',
            'shift' => 'Shift Pagi',
            'operator_name' => 'Operator A',
            'raw_cons_wtp' => 10,
            'dm_prod' => 5,
        ]);

        $this->withJwt()->getJson('/api/reports/monthly-consumption?month=2026-05')
            ->assertOk()
            ->assertJsonPath('data.period.month', '2026-05')
            ->assertJsonPath('data.excel.filename', 'monthly_consumption_2026-05.xlsx')
            ->assertJsonPath('data.excel.sheets.0.rows.14.raw_cons_wtp', 10)
            ->assertJsonPath('data.excel.sheets.0.rows.14.dm_prod', 5);
    }

    public function test_monthly_report_requires_valid_month(): void
    {
        $this->withJwt()->getJson('/api/reports/monthly-consumption?month=2026')
            ->assertStatus(400)
            ->assertJsonValidationErrors(['month']);
    }

    public function test_can_get_monthly_water_analysis_report_excel_data(): void
    {
        $this->createMasterLocation();
        DB::table('log_sheet_wtp')->insert([
            'log_date' => '2026-05-15 08:00:00',
            'shift' => 'Shift Pagi',
            'lokasi' => 'LOC001',
            'ph' => 7.5,
            'cond' => 11.2,
            'operator_name' => 'Operator A',
        ]);

        $this->withJwt()->getJson('/api/reports/monthly-water-analysis?month=2026-05')
            ->assertOk()
            ->assertJsonPath('data.period.month', '2026-05')
            ->assertJsonPath('data.excel.filename', 'monthly_water_analysis_2026-05.xlsx')
            ->assertJsonPath('data.excel.sheets.0.rows.0.source', 'WTP')
            ->assertJsonPath('data.excel.sheets.0.rows.0.avg_ph', 7.5);
    }

    public function test_can_get_daily_water_analysis_report_excel_data(): void
    {
        $this->createMasterLocation();
        DB::table('log_sheet_wtp')->insert([
            'log_date' => '2026-05-15 08:00:00',
            'shift' => 'Shift Pagi',
            'lokasi' => 'LOC001',
            'ph' => 7.5,
            'cond' => 11.2,
            'operator_name' => 'Operator A',
        ]);

        $this->withJwt()->getJson('/api/reports/daily-water-analysis?date=2026-05-15')
            ->assertOk()
            ->assertJsonPath('data.date', '2026-05-15')
            ->assertJsonPath('data.excel.filename', 'daily_water_analysis_2026-05-15.xlsx')
            ->assertJsonPath('data.excel.sheets.0.rows.0.section', 'WTP')
            ->assertJsonPath('data.excel.sheets.0.rows.0.metric_01', 7.5);
    }

    public function test_daily_report_requires_valid_date(): void
    {
        $this->withJwt()->getJson('/api/reports/daily-water-analysis?date=15-05-2026')
            ->assertStatus(400)
            ->assertJsonValidationErrors(['date']);
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

    public function test_can_create_ro_plant_with_master_standard_ro_location(): void
    {
        DB::table('master_standard_ro')->insert([
            'loc_id' => 'RO001',
            'location_name' => 'SWRO PRO',
        ]);

        $id = $this->withJwt()->postJson('/api/log-sheets/ro-plant', [
            'log_date' => '2026-05-15 08:00:00',
            'shift' => 'Shift 1',
            'lokasi' => 'RO001',
            'ph' => 7.1,
            'cond' => 12.3,
            'th' => 10.2,
        ])
            ->assertCreated()
            ->assertJsonPath('data.lokasi', 'RO001')
            ->json('data.log_id');

        $this->withJwt()->getJson('/api/log-sheets/ro-plant/'.$id)
            ->assertOk()
            ->assertJsonPath('data.lokasi', 'RO001')
            ->assertJsonPath('data.location_name', 'SWRO PRO');
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

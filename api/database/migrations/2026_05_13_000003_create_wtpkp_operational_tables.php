<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('allowed_domains', function (Blueprint $table) {
            $table->id('domain_id');
            $table->string('domain_name', 50)->unique();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id('role_id');
            $table->string('role_name', 50)->unique();
        });

        Schema::create('wtp_users', function (Blueprint $table) {
            $table->id('user_id');
            $table->string('email', 255)->unique();
            $table->string('full_name', 255);
            $table->foreignId('role_id')->nullable()->default(2)->constrained('roles', 'role_id');
            $table->timestamp('created_at')->nullable()->useCurrent();
        });

        Schema::create('user_auth', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->primary();
            $table->string('password_hash', 512);
            $table->string('salt', 64)->nullable();
            $table->foreign('user_id')->references('user_id')->on('wtp_users')->cascadeOnDelete();
        });

        Schema::create('master_locations', function (Blueprint $table) {
            $table->string('location_code', 10)->primary();
            $table->string('location_name', 100);
            $this->decimalColumns($table, ['ph_min', 'ph_max', 'cond_max', 'tds_max', 'p_alk_max', 'm_alk_max', 'th_max', 'sio2_max', 'po4_max'], 10, 3);
        });

        Schema::create('master_standard_ro', function (Blueprint $table) {
            $table->string('loc_id', 20)->primary();
            $table->string('location_name', 100)->nullable();
            $table->float('ph_min')->nullable();
            $table->float('ph_max')->nullable();
            foreach (['cond_max', 'th_max', 'cah_max', 'mgh_max', 't_alk_max', 't_cl_max', 'iron_max', 'frc_max', 'turb_max'] as $column) {
                $table->string($column, 20)->nullable();
            }
        });

        Schema::create('master_standard_ro_process', function (Blueprint $table) {
            $table->string('loc_id', 10)->primary();
            $table->string('location_name', 100)->nullable();
            $this->decimalColumns($table, ['ph_min', 'ph_max', 'cond_max', 'sio2_max', 'turb_max', 'temp_max'], 10, 2);
        });

        Schema::create('log_sheet_wtp', function (Blueprint $table) {
            $table->id('log_id');
            $table->timestamp('log_date')->nullable()->useCurrent();
            $table->string('shift', 20)->nullable();
            $table->string('lokasi', 10);
            $this->decimalColumns($table, ['ph', 'cond', 'tds', 'p_alk', 'm_alk', 'th', 'sio2', 'po4'], 10, 3);
            $table->string('operator_name', 50);
            $table->foreign('lokasi')->references('location_code')->on('master_locations');
            $table->index(['log_date', 'lokasi']);
        });

        Schema::create('log_sheet_ro_plant', function (Blueprint $table) {
            $table->id('log_id');
            $table->timestamp('log_date')->nullable()->useCurrent();
            $table->string('shift', 20)->nullable();
            $table->string('lokasi', 10);
            $this->decimalColumns($table, ['ph', 'cond', 'th', 'cah', 'mgh', 't_alk', 't_cl', 'iron', 'frc', 'turbidity'], 10, 3);
            $table->string('operator_name', 50);
            $table->index(['log_date', 'lokasi']);
        });

        Schema::create('log_sheet_ro_process', function (Blueprint $table) {
            $table->id('log_id');
            $table->timestamp('log_date')->nullable()->useCurrent();
            $table->string('shift', 20)->nullable();
            $table->string('lokasi', 50);
            $this->decimalColumns($table, ['ph', 'cond', 'th', 'cah', 'mgh', 't_alk', 't_cl', 'sio2', 'frc', 'turbidity', 'temp', 'coc'], 10, 3);
            $table->string('operator_name', 50);
            $table->index(['log_date', 'lokasi']);
        });

        Schema::create('log_sheet_wwtp_ol', function (Blueprint $table) {
            $table->id('log_id');
            $this->logColumns($table, 50);
            $table->string('unit_name', 20)->nullable();
            $this->decimalColumns($table, ['cu', 'zn', 'cr', 'tss', 'fe', 'po5'], 10, 2);
            $table->index(['log_date', 'unit_name']);
        });

        Schema::create('log_sheet_chemical_usage', function (Blueprint $table) {
            $table->id('log_id');
            $table->date('log_date')->nullable()->useCurrent();
            $table->string('operator_name', 50);
            $this->decimalColumns($table, ['pac', 'poly', 'bio_pre', 'bio_ro', 'cl2_sgr', 'ro_ant', 'naoh', 'hcl', 'tsp', 'o2_sgr', 'nh3', 'biocide', 'bio_acw_kgs', 'tsp_acw_kgs', 'naoh_acw_kgs', 'bio_mcw_kgs', 'ctscale_mcw_kgs', 'pac_wwtp_kgs', 'poly_wwtp_kgs', 'bio_wwtp_kgs'], 10, 3, 0);
            $table->timestamp('input_time')->nullable()->useCurrent();
            $table->index('log_date');
        });

        Schema::create('log_sheet_coal_sieve', function (Blueprint $table) {
            $table->id('log_id');
            $this->logColumns($table, 50);
            $this->decimalColumns($table, ['size_above_10mm', 'size_6mm_10mm', 'size_4mm_6mm', 'size_below_4mm', 'total_percentage'], 10, 2);
            $table->index('log_date');
        });

        Schema::create('log_sheet_condenser', function (Blueprint $table) {
            $table->id('log_id');
            $this->logColumns($table, 50);
            $table->string('unit_name', 20)->nullable();
            $this->decimalColumns($table, ['inlet_pres', 'outlet_pres', 'delta_p', 'approach_mcw', 'range_mcw'], 10, 3);
            $this->decimalColumns($table, ['inlet_temp', 'outlet_temp', 'delta_t', 'vacuum_pres', 'acw_in_temp', 'acw_out_temp', 'acw_range', 'acw_approach'], 10, 2);
            $table->index(['log_date', 'unit_name']);
        });

        Schema::create('log_sheet_mb_running_data', function (Blueprint $table) {
            $table->id('log_id');
            $this->logColumns($table, 100);
            $this->decimalColumns($table, ['mb_i', 'mb_ii', 'mb_i_obr_m3', 'mb_2_obr_m3', 'rg_std_mb'], 10, 2);
            $table->string('last_obr_m3', 100)->nullable();
            $table->index('log_date');
        });

        Schema::create('log_sheet_ro_pump_running_hours', function (Blueprint $table) {
            $table->id('log_id');
            $this->logColumns($table, 100);
            $table->string('intake_val', 50)->nullable();
            $this->decimalColumns($table, ['mmf_bw', 'mmf_fp', 'dtr_dea', 'dtr_drain', 'swro1_hp1', 'swro2_hp2', 'acw_val'], 10, 2);
            $table->string('raw_tr', 50)->nullable();
            $table->string('mcw_val', 50)->nullable();
            $table->index('log_date');
        });

        Schema::create('log_sheet_swro_uf_pressure', function (Blueprint $table) {
            $table->id('log_id');
            $this->logColumns($table, 100);
            $table->string('location_code', 50)->nullable();
            $this->decimalColumns($table, ['inlet_pres', 'outlet_pres', 'delta_p', 'prod_pres', 'prod_flow', 'reject_m3', 'permeate_avg_m3'], 10, 2);
            $table->index(['log_date', 'location_code']);
        });

        Schema::create('log_sheet_bwro_pressure', function (Blueprint $table) {
            $table->id('log_id');
            $this->logColumns($table, 100);
            $table->string('location_code', 50)->nullable();
            $this->decimalColumns($table, ['inlet_pres_1', 'inlet_pres_2', 'outlet_pres', 'delta_p', 'prod_pres'], 10, 2);
            $this->decimalColumns($table, ['reject_m3', 'permit_m3'], 18, 2);
            $table->index(['log_date', 'location_code']);
        });

        Schema::create('log_sheet_water_production', function (Blueprint $table) {
            $table->id('log_id');
            $table->timestamp('log_date')->nullable()->useCurrent();
            $table->string('shift', 10)->nullable();
            $table->string('operator_name', 50);
            $this->decimalColumns($table, ['dm_prod', 'dm_cons', 'dm_stock_mtr', 'dm_stock_m3_calc', 'dm_stock_m3_actual', 'drain_to_deaerator', 'fresh_prod', 'fresh_cons', 'fresh_stock_mtr', 'fresh_stock_m3', 'raw_cons_wtp', 'rw_storage_mtr', 'rw_storage_m3', 'aux_ct_basin_mtr', 'aux_ct_basin_m3', 'main_ct_basin_mtr', 'main_ct_basin_m3', 'drain_tank_mtr', 'drain_tank_m3', 'deaerator_mm', 'deaerator_m3'], 10, 3);
            $table->timestamp('input_time_dm')->nullable();
            $table->timestamp('input_time_fw')->nullable();
            $table->timestamp('input_time_ro')->nullable();
            $table->timestamp('input_time_other')->nullable();
            $table->index(['log_date', 'shift']);
        });
    }

    public function down(): void
    {
        foreach ([
            'log_sheet_water_production',
            'log_sheet_bwro_pressure',
            'log_sheet_swro_uf_pressure',
            'log_sheet_ro_pump_running_hours',
            'log_sheet_mb_running_data',
            'log_sheet_condenser',
            'log_sheet_coal_sieve',
            'log_sheet_chemical_usage',
            'log_sheet_wwtp_ol',
            'log_sheet_ro_process',
            'log_sheet_ro_plant',
            'log_sheet_wtp',
            'master_standard_ro_process',
            'master_standard_ro',
            'master_locations',
            'user_auth',
            'wtp_users',
            'roles',
            'allowed_domains',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }

    private function logColumns(Blueprint $table, int $operatorLength): void
    {
        $table->date('log_date')->nullable()->useCurrent();
        $table->timestamp('input_time')->nullable()->useCurrent();
        $table->string('operator_name', $operatorLength);
    }

    private function decimalColumns(Blueprint $table, array $columns, int $precision, int $scale, ?int $default = null): void
    {
        foreach ($columns as $column) {
            $definition = $table->decimal($column, $precision, $scale)->nullable();
            if ($default !== null) {
                $definition->default($default);
            }
        }
    }
};

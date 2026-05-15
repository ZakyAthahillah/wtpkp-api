<?php

namespace App\Helpers;

use Illuminate\Validation\Rule;

class LogSheetResourceRegistry
{
    public static function all(): array
    {
        return [
            'allowed-domains' => [
                'table' => 'allowed_domains',
                'primaryKey' => 'domain_id',
                'fields' => ['domain_name'],
                'rules' => ['domain_name' => ['required', 'string', 'max:50']],
                'filters' => ['domain_name'],
                'sort' => 'domain_id',
            ],
            'roles' => [
                'table' => 'roles',
                'primaryKey' => 'role_id',
                'fields' => ['role_name'],
                'rules' => ['role_name' => ['required', 'string', 'max:50']],
                'filters' => ['role_name'],
                'sort' => 'role_id',
            ],
            'users' => [
                'table' => 'wtp_users',
                'primaryKey' => 'user_id',
                'fields' => ['username', 'email', 'full_name', 'role_id', 'created_at'],
                'rules' => [
                    'username' => ['required', 'string', 'max:100'],
                    'email' => ['nullable', 'email', 'max:255'],
                    'full_name' => ['required', 'string', 'max:255'],
                    'role_id' => ['nullable', 'integer'],
                    'created_at' => ['nullable', 'date'],
                ],
                'filters' => ['username', 'email', 'full_name', 'role_id'],
                'sort' => 'user_id',
            ],
            'user-auth' => [
                'table' => 'user_auth',
                'primaryKey' => 'user_id',
                'fields' => ['user_id', 'password_hash', 'salt'],
                'rules' => [
                    'user_id' => ['required', 'integer'],
                    'password_hash' => ['required', 'string', 'max:512'],
                    'salt' => ['nullable', 'string', 'max:64'],
                ],
                'filters' => ['user_id'],
                'sort' => 'user_id',
            ],
            'master-locations' => [
                'table' => 'master_locations',
                'primaryKey' => 'location_code',
                'fields' => ['location_code', 'location_name', 'ph_min', 'ph_max', 'cond_max', 'tds_max', 'p_alk_max', 'm_alk_max', 'th_max', 'sio2_max', 'po4_max'],
                'rules' => self::standardRules(['location_code' => ['required', 'string', 'max:10'], 'location_name' => ['required', 'string', 'max:100']]),
                'filters' => ['location_code', 'location_name'],
                'sort' => 'location_code',
            ],
            'master-standard-ro' => [
                'table' => 'master_standard_ro',
                'primaryKey' => 'loc_id',
                'fields' => ['loc_id', 'location_name', 'ph_min', 'ph_max', 'cond_max', 'th_max', 'cah_max', 'mgh_max', 't_alk_max', 't_cl_max', 'iron_max', 'frc_max', 'turb_max'],
                'rules' => [
                    'loc_id' => ['required', 'string', 'max:20'],
                    'location_name' => ['nullable', 'string', 'max:100'],
                    'ph_min' => ['nullable', 'numeric'],
                    'ph_max' => ['nullable', 'numeric'],
                    'cond_max' => ['nullable', 'string', 'max:20'],
                    'th_max' => ['nullable', 'string', 'max:20'],
                    'cah_max' => ['nullable', 'string', 'max:20'],
                    'mgh_max' => ['nullable', 'string', 'max:20'],
                    't_alk_max' => ['nullable', 'string', 'max:20'],
                    't_cl_max' => ['nullable', 'string', 'max:20'],
                    'iron_max' => ['nullable', 'string', 'max:20'],
                    'frc_max' => ['nullable', 'string', 'max:20'],
                    'turb_max' => ['nullable', 'string', 'max:20'],
                ],
                'filters' => ['loc_id', 'location_name'],
                'sort' => 'loc_id',
            ],
            'master-standard-ro-process' => [
                'table' => 'master_standard_ro_process',
                'primaryKey' => 'loc_id',
                'fields' => ['loc_id', 'location_name', 'ph_min', 'ph_max', 'cond_max', 'sio2_max', 'turb_max', 'temp_max'],
                'rules' => self::standardRules(['loc_id' => ['required', 'string', 'max:10'], 'location_name' => ['nullable', 'string', 'max:100']]),
                'filters' => ['loc_id', 'location_name'],
                'sort' => 'loc_id',
            ],
            'wtp' => self::analysisResource('log_sheet_wtp', ['tds', 'p_alk', 'm_alk', 'sio2', 'po4'], ['table' => 'master_locations', 'key' => 'location_code']),
            'ro-plant' => self::analysisResource('log_sheet_ro_plant', ['cah', 'mgh', 't_alk', 't_cl', 'iron'], ['table' => 'master_standard_ro', 'key' => 'loc_id']),
            'ro-process' => self::analysisResource('log_sheet_ro_process', ['cah', 'mgh', 't_alk', 't_cl', 'sio2', 'temp', 'coc'], ['table' => 'master_standard_ro_process', 'key' => 'loc_id']),
            'wwtp-ol' => self::loggedResource('log_sheet_wwtp_ol', ['unit_name', 'cu', 'zn', 'cr', 'tss', 'fe', 'po5']),
            'chemical-usage' => self::loggedResource('log_sheet_chemical_usage', ['pac', 'poly', 'bio_pre', 'bio_ro', 'cl2_sgr', 'ro_ant', 'naoh', 'hcl', 'tsp', 'o2_sgr', 'nh3', 'biocide', 'bio_acw_kgs', 'tsp_acw_kgs', 'naoh_acw_kgs', 'bio_mcw_kgs', 'ctscale_mcw_kgs', 'pac_wwtp_kgs', 'poly_wwtp_kgs', 'bio_wwtp_kgs']),
            'coal-sieve' => self::loggedResource('log_sheet_coal_sieve', ['size_above_10mm', 'size_6mm_10mm', 'size_4mm_6mm', 'size_below_4mm', 'total_percentage']),
            'condenser' => self::loggedResource('log_sheet_condenser', ['unit_name', 'inlet_pres', 'outlet_pres', 'delta_p', 'approach_mcw', 'range_mcw', 'inlet_temp', 'outlet_temp', 'delta_t', 'vacuum_pres', 'acw_in_temp', 'acw_out_temp', 'acw_range', 'acw_approach']),
            'mb-running-data' => self::loggedResource('log_sheet_mb_running_data', ['mb_i', 'mb_ii', 'mb_i_obr_m3', 'mb_2_obr_m3', 'rg_std_mb', 'last_obr_m3']),
            'ro-pump-running-hours' => self::loggedResource('log_sheet_ro_pump_running_hours', ['intake_val', 'mmf_bw', 'mmf_fp', 'dtr_dea', 'dtr_drain', 'swro1_hp1', 'swro2_hp2', 'acw_val', 'raw_tr', 'mcw_val']),
            'swro-uf-pressure' => self::loggedResource('log_sheet_swro_uf_pressure', ['location_code', 'inlet_pres', 'outlet_pres', 'delta_p', 'prod_pres', 'prod_flow', 'reject_m3', 'permeate_avg_m3']),
            'bwro-pressure' => self::loggedResource('log_sheet_bwro_pressure', ['location_code', 'inlet_pres_1', 'inlet_pres_2', 'outlet_pres', 'delta_p', 'prod_pres', 'reject_m3', 'permit_m3']),
            'water-production' => self::waterProductionResource(),
        ];
    }

    public static function find(string $resource): ?array
    {
        return self::all()[$resource] ?? null;
    }

    private static function analysisResource(string $table, array $extraFields, array $locationMaster): array
    {
        $fields = array_merge(['log_date', 'shift', 'lokasi', 'ph', 'cond', 'th'], $extraFields, ['frc', 'turbidity', 'operator_name']);
        $fields = array_values(array_unique($fields));
        $rules = [
            'log_date' => ['nullable', 'date'],
            'shift' => ['nullable', 'string', 'max:20', Rule::in(ShiftNormalizer::allowedValues())],
            'lokasi' => ['required', 'string', 'max:50', Rule::exists($locationMaster['table'], $locationMaster['key'])],
            'operator_name' => ['nullable', 'string', 'max:50'],
        ];

        foreach ($fields as $field) {
            if (! isset($rules[$field])) {
                $rules[$field] = ['nullable', 'numeric'];
            }
        }

        return [
            'table' => $table,
            'primaryKey' => 'log_id',
            'fields' => $fields,
            'rules' => $rules,
            'filters' => ['log_date', 'shift', 'lokasi', 'operator_name'],
            'sort' => 'log_id',
            'locationMaster' => $locationMaster,
        ];
    }

    private static function loggedResource(string $table, array $extraFields): array
    {
        $fields = array_merge(['log_date', 'input_time', 'operator_name'], $extraFields);
        $rules = [
            'log_date' => ['nullable', 'date'],
            'input_time' => ['nullable', 'date'],
            'operator_name' => ['nullable', 'string', 'max:100'],
        ];

        foreach ($extraFields as $field) {
            $rules[$field] = str_ends_with($field, '_name') || in_array($field, ['location_code', 'intake_val', 'raw_tr', 'mcw_val', 'last_obr_m3'], true)
                ? self::loggedStringRules($field)
                : ['nullable', 'numeric'];
        }

        return [
            'table' => $table,
            'primaryKey' => 'log_id',
            'fields' => $fields,
            'rules' => $rules,
            'filters' => ['log_date', 'operator_name', 'location_code', 'unit_name'],
            'sort' => 'log_id',
        ];
    }

    private static function waterProductionResource(): array
    {
        $fields = ['log_date', 'shift', 'operator_name', 'dm_prod', 'dm_cons', 'dm_stock_mtr', 'dm_stock_m3_calc', 'dm_stock_m3_actual', 'drain_to_deaerator', 'fresh_prod', 'fresh_cons', 'fresh_stock_mtr', 'fresh_stock_m3', 'raw_cons_wtp', 'rw_storage_mtr', 'rw_storage_m3', 'aux_ct_basin_mtr', 'aux_ct_basin_m3', 'main_ct_basin_mtr', 'main_ct_basin_m3', 'drain_tank_mtr', 'drain_tank_m3', 'deaerator_mm', 'deaerator_m3', 'input_time_dm', 'input_time_fw', 'input_time_ro', 'input_time_other'];
        $rules = ['log_date' => ['nullable', 'date'], 'shift' => ['nullable', 'string', 'max:20', Rule::in(ShiftNormalizer::allowedValues())], 'operator_name' => ['nullable', 'string', 'max:50']];

        foreach ($fields as $field) {
            if (! isset($rules[$field])) {
                $rules[$field] = str_starts_with($field, 'input_time') ? ['nullable', 'date'] : ['nullable', 'numeric'];
            }
        }

        return [
            'table' => 'log_sheet_water_production',
            'primaryKey' => 'log_id',
            'fields' => $fields,
            'rules' => $rules,
            'filters' => ['log_date', 'shift', 'operator_name'],
            'sort' => 'log_id',
        ];
    }

    private static function standardRules(array $required): array
    {
        return array_merge($required, [
            'ph_min' => ['nullable', 'numeric'],
            'ph_max' => ['nullable', 'numeric'],
            'cond_max' => ['nullable', 'numeric'],
            'tds_max' => ['nullable', 'numeric'],
            'p_alk_max' => ['nullable', 'numeric'],
            'm_alk_max' => ['nullable', 'numeric'],
            'th_max' => ['nullable', 'numeric'],
            'sio2_max' => ['nullable', 'numeric'],
            'po4_max' => ['nullable', 'numeric'],
            'turb_max' => ['nullable', 'numeric'],
            'temp_max' => ['nullable', 'numeric'],
        ]);
    }

    private static function loggedStringRules(string $field): array
    {
        if ($field === 'location_code') {
            return ['nullable', 'string', 'max:100', Rule::exists('master_locations', 'location_code')];
        }

        return ['nullable', 'string', 'max:100'];
    }
}

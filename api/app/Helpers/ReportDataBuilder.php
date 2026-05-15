<?php

namespace App\Helpers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportDataBuilder
{
    public static function monthlyConsumption(string $month): array
    {
        [$start, $end] = self::monthRange($month);
        $rows = self::dateRows($start, $end)->map(function (string $date) {
            $water = DB::table('log_sheet_water_production')->whereDate('log_date', $date)->get();
            $swro = DB::table('log_sheet_swro_uf_pressure')->whereDate('log_date', $date)->get();
            $bwro = DB::table('log_sheet_bwro_pressure')->whereDate('log_date', $date)->get();
            $chemical = DB::table('log_sheet_chemical_usage')->whereDate('log_date', $date)->get();

            return array_merge(['date' => $date], self::waterProductionDaily($water), self::roFlowDaily($swro, $bwro), self::chemicalDaily($chemical));
        })->values();

        $columns = self::monthlyConsumptionColumns();

        return [
            'period' => ['month' => $month, 'start_date' => $start->toDateString(), 'end_date' => $end->toDateString()],
            'rows' => $rows,
            'excel' => self::excelPayload('monthly_consumption_'.$month.'.xlsx', 'Monthly Consumption', $columns, $rows),
        ];
    }

    public static function monthlyWaterAnalysis(string $month): array
    {
        [$start, $end] = self::monthRange($month);
        $rows = collect()
            ->merge(self::monthlyWtpRows($start, $end))
            ->merge(self::monthlyRoRows($start, $end))
            ->merge(self::monthlyChemicalRows($start, $end))
            ->values();

        $columns = [
            ['key' => 'source', 'label' => 'Source'],
            ['key' => 'location_code', 'label' => 'Location Code'],
            ['key' => 'location_name', 'label' => 'Location Name'],
            ['key' => 'date', 'label' => 'Date'],
            ['key' => 'total_rows', 'label' => 'Rows'],
            ['key' => 'avg_ph', 'label' => 'Avg pH'],
            ['key' => 'avg_cond', 'label' => 'Avg Cond'],
            ['key' => 'avg_tds', 'label' => 'Avg TDS'],
            ['key' => 'avg_p_alk', 'label' => 'Avg P.Alk'],
            ['key' => 'avg_m_alk', 'label' => 'Avg M.Alk'],
            ['key' => 'avg_th', 'label' => 'Avg TH'],
            ['key' => 'avg_sio2', 'label' => 'Avg SiO2'],
            ['key' => 'avg_po4', 'label' => 'Avg PO4'],
            ['key' => 'last_update', 'label' => 'Last Update'],
        ];

        return [
            'period' => ['month' => $month, 'start_date' => $start->toDateString(), 'end_date' => $end->toDateString()],
            'rows' => $rows,
            'excel' => self::excelPayload('monthly_water_analysis_'.$month.'.xlsx', 'Monthly Water Analysis', $columns, $rows),
        ];
    }

    public static function dailyWaterAnalysis(string $date): array
    {
        $day = CarbonImmutable::parse($date)->toDateString();
        $rows = collect()
            ->merge(self::dailyWtpRows($day))
            ->merge(self::dailyRoRows($day))
            ->merge(self::dailySingleRows($day))
            ->values();

        $columns = [
            ['key' => 'section', 'label' => 'Section'],
            ['key' => 'code', 'label' => 'Code'],
            ['key' => 'shift', 'label' => 'Shift'],
            ['key' => 'metric_01', 'label' => 'Metric 01'],
            ['key' => 'metric_02', 'label' => 'Metric 02'],
            ['key' => 'metric_03', 'label' => 'Metric 03'],
            ['key' => 'metric_04', 'label' => 'Metric 04'],
            ['key' => 'metric_05', 'label' => 'Metric 05'],
            ['key' => 'metric_06', 'label' => 'Metric 06'],
            ['key' => 'metric_07', 'label' => 'Metric 07'],
            ['key' => 'metric_08', 'label' => 'Metric 08'],
            ['key' => 'metric_09', 'label' => 'Metric 09'],
            ['key' => 'metric_10', 'label' => 'Metric 10'],
        ];

        return [
            'date' => $day,
            'rows' => $rows,
            'excel' => self::excelPayload('daily_water_analysis_'.$day.'.xlsx', 'Daily Water Analysis', $columns, $rows),
        ];
    }

    private static function monthRange(string $month): array
    {
        $start = CarbonImmutable::createFromFormat('Y-m-d', $month.'-01')->startOfDay();

        return [$start, $start->endOfMonth()];
    }

    private static function dateRows(CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        $dates = collect();
        for ($date = $start; $date->lessThanOrEqualTo($end); $date = $date->addDay()) {
            $dates->push($date->toDateString());
        }

        return $dates;
    }

    private static function waterProductionDaily(Collection $rows): array
    {
        return [
            'raw_cons_wtp' => self::sum($rows, 'raw_cons_wtp'),
            'rw_storage_m3' => self::max($rows, 'rw_storage_m3'),
            'drain_tank_m3' => self::sum($rows, 'drain_tank_m3'),
            'aux_ct_basin_m3' => self::max($rows, 'aux_ct_basin_m3'),
            'main_ct_basin_m3' => self::max($rows, 'main_ct_basin_m3'),
            'dm_prod' => self::sum($rows, 'dm_prod'),
            'dm_cons' => self::sum($rows, 'dm_cons'),
            'dm_stock_m3_actual' => self::max($rows, 'dm_stock_m3_actual'),
            'fresh_prod' => self::sum($rows, 'fresh_prod'),
            'fresh_cons' => self::sum($rows, 'fresh_cons'),
            'fresh_stock_m3' => self::max($rows, 'fresh_stock_m3'),
        ];
    }

    private static function roFlowDaily(Collection $swro, Collection $bwro): array
    {
        return [
            'swro1_pro_avg' => self::avg($swro->where('location_code', 'LOC023'), 'permeate_avg_m3'),
            'swro2_pro_avg' => self::avg($swro->where('location_code', 'LOC020'), 'permeate_avg_m3'),
            'swro1_reject' => self::sum($swro->where('location_code', 'LOC023'), 'reject_m3'),
            'swro2_reject' => self::sum($swro->where('location_code', 'LOC020'), 'reject_m3'),
            'bwro1_reject' => self::sum($bwro->where('location_code', 'LOC017'), 'reject_m3'),
            'bwro2_reject' => self::sum($bwro->where('location_code', 'LOC018'), 'reject_m3'),
            'bwro1_pro_avg' => self::avg($bwro->where('location_code', 'LOC017'), 'permit_m3'),
            'bwro2_pro_avg' => self::avg($bwro->where('location_code', 'LOC018'), 'permit_m3'),
        ];
    }

    private static function chemicalDaily(Collection $rows): array
    {
        $fields = ['pac', 'poly', 'bio_pre', 'bio_ro', 'cl2_sgr', 'ro_ant', 'naoh', 'hcl', 'tsp', 'o2_sgr', 'nh3', 'biocide', 'bio_acw_kgs', 'tsp_acw_kgs', 'naoh_acw_kgs', 'bio_mcw_kgs', 'ctscale_mcw_kgs', 'pac_wwtp_kgs', 'poly_wwtp_kgs', 'bio_wwtp_kgs'];

        return collect($fields)
            ->mapWithKeys(fn (string $field) => [$field => self::sum($rows, $field)])
            ->all();
    }

    private static function monthlyWtpRows(CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        $masters = DB::table('master_locations')->get()->keyBy('location_code');

        return DB::table('log_sheet_wtp')
            ->whereBetween('log_date', [$start->startOfDay(), $end->endOfDay()])
            ->get()
            ->groupBy(fn (object $row) => $row->lokasi.'|'.CarbonImmutable::parse($row->log_date)->toDateString())
            ->map(function (Collection $items, string $key) use ($masters) {
                [$code, $date] = explode('|', $key);
                $master = $masters->get($code);

                return [
                    'source' => 'WTP',
                    'location_code' => $code,
                    'location_name' => $master?->location_name,
                    'date' => $date,
                    'total_rows' => $items->count(),
                    'avg_ph' => self::avg($items, 'ph'),
                    'avg_cond' => self::avg($items, 'cond'),
                    'avg_tds' => self::avg($items, 'tds'),
                    'avg_p_alk' => self::avg($items, 'p_alk'),
                    'avg_m_alk' => self::avg($items, 'm_alk'),
                    'avg_th' => self::avg($items, 'th'),
                    'avg_sio2' => self::avg($items, 'sio2'),
                    'avg_po4' => self::avg($items, 'po4'),
                    'last_update' => self::maxDate($items, 'log_date'),
                ];
            })
            ->values();
    }

    private static function monthlyRoRows(CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        return collect([
            ['source' => 'RO_PROCESS', 'table' => 'log_sheet_ro_process'],
            ['source' => 'RO_PLANT', 'table' => 'log_sheet_ro_plant'],
        ])->flatMap(function (array $definition) use ($start, $end) {
            return DB::table($definition['table'])
                ->whereBetween('log_date', [$start->startOfDay(), $end->endOfDay()])
                ->get()
                ->groupBy(fn (object $row) => $row->lokasi.'|'.CarbonImmutable::parse($row->log_date)->toDateString())
                ->map(function (Collection $items, string $key) use ($definition) {
                    [$code, $date] = explode('|', $key);

                    return [
                        'source' => $definition['source'],
                        'location_code' => $code,
                        'location_name' => null,
                        'date' => $date,
                        'total_rows' => $items->count(),
                        'avg_ph' => self::avg($items, 'ph'),
                        'avg_cond' => self::avg($items, 'cond'),
                        'avg_tds' => null,
                        'avg_p_alk' => null,
                        'avg_m_alk' => null,
                        'avg_th' => self::avg($items, 'th'),
                        'avg_sio2' => $definition['source'] === 'RO_PROCESS' ? self::avg($items, 'sio2') : null,
                        'avg_po4' => null,
                        'last_update' => self::maxDate($items, 'log_date'),
                    ];
                });
        })->values();
    }

    private static function monthlyChemicalRows(CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        $rows = DB::table('log_sheet_chemical_usage')
            ->whereBetween('log_date', [$start->toDateString(), $end->toDateString()])
            ->get();

        return collect(self::chemicalDaily($rows))
            ->map(fn (float $value, string $field) => [
                'source' => 'CHEM',
                'location_code' => $field,
                'location_name' => strtoupper(str_replace('_', ' ', $field)),
                'date' => $start->toDateString(),
                'total_rows' => $rows->count(),
                'avg_ph' => $value,
                'avg_cond' => null,
                'avg_tds' => null,
                'avg_p_alk' => null,
                'avg_m_alk' => null,
                'avg_th' => null,
                'avg_sio2' => null,
                'avg_po4' => null,
                'last_update' => self::maxDate($rows, 'input_time'),
            ])
            ->values();
    }

    private static function dailyWtpRows(string $date): Collection
    {
        return DB::table('log_sheet_wtp')
            ->whereDate('log_date', $date)
            ->get()
            ->groupBy(fn (object $row) => $row->lokasi.'|'.($row->shift ?? ''))
            ->map(function (Collection $items, string $key) {
                [$code, $shift] = explode('|', $key);

                return [
                    'section' => 'WTP',
                    'code' => $code,
                    'shift' => $shift,
                    'metric_01' => self::avg($items, 'ph'),
                    'metric_02' => self::avg($items, 'cond'),
                    'metric_03' => self::avg($items, 'tds'),
                    'metric_04' => self::avg($items, 'p_alk'),
                    'metric_05' => self::avg($items, 'm_alk'),
                    'metric_06' => self::avg($items, 'th'),
                    'metric_07' => self::avg($items, 'sio2'),
                    'metric_08' => self::avg($items, 'po4'),
                    'metric_09' => null,
                    'metric_10' => null,
                ];
            })
            ->values();
    }

    private static function dailyRoRows(string $date): Collection
    {
        return collect([
            ['section' => 'RO_PROCESS', 'table' => 'log_sheet_ro_process'],
            ['section' => 'RO_PLANT', 'table' => 'log_sheet_ro_plant'],
        ])->flatMap(function (array $definition) use ($date) {
            return DB::table($definition['table'])
                ->whereDate('log_date', $date)
                ->get()
                ->groupBy('lokasi')
                ->map(fn (Collection $items, string $code) => [
                    'section' => $definition['section'],
                    'code' => $code,
                    'shift' => '',
                    'metric_01' => self::avg($items, 'ph'),
                    'metric_02' => self::avg($items, 'cond'),
                    'metric_03' => self::avg($items, 'th'),
                    'metric_04' => self::avg($items, 'cah'),
                    'metric_05' => self::avg($items, 'mgh'),
                    'metric_06' => self::avg($items, 't_alk'),
                    'metric_07' => self::avg($items, 't_cl'),
                    'metric_08' => self::avg($items, 'frc'),
                    'metric_09' => self::avg($items, 'turbidity'),
                    'metric_10' => $definition['section'] === 'RO_PROCESS' ? self::avg($items, 'temp') : self::avg($items, 'iron'),
                ]);
        })->values();
    }

    private static function dailySingleRows(string $date): Collection
    {
        return collect([
            self::dailySectionRow('PRODUCTION', 'DAY', DB::table('log_sheet_water_production')->whereDate('log_date', $date)->get(), ['raw_cons_wtp', 'rw_storage_m3', 'drain_tank_m3', 'aux_ct_basin_m3', 'main_ct_basin_m3', 'dm_prod', 'dm_cons', 'dm_stock_m3_actual', 'fresh_prod', 'fresh_cons']),
            self::dailySectionRow('CHEM', 'DAY', DB::table('log_sheet_chemical_usage')->whereDate('log_date', $date)->get(), ['pac', 'poly', 'bio_pre', 'bio_ro', 'cl2_sgr', 'ro_ant', 'naoh', 'hcl', 'tsp', 'o2_sgr']),
            self::dailySectionRow('WWTP', 'LOC029', DB::table('log_sheet_wwtp_ol')->whereDate('log_date', $date)->where('unit_name', 'LOC029')->get(), ['cu', 'zn', 'cr', 'tss', 'fe', 'po5']),
        ]);
    }

    private static function dailySectionRow(string $section, string $code, Collection $rows, array $fields): array
    {
        $data = ['section' => $section, 'code' => $code, 'shift' => ''];
        for ($i = 1; $i <= 10; $i++) {
            $field = $fields[$i - 1] ?? null;
            $data['metric_'.str_pad((string) $i, 2, '0', STR_PAD_LEFT)] = $field ? self::sum($rows, $field) : null;
        }

        return $data;
    }

    private static function monthlyConsumptionColumns(): array
    {
        return collect(['date', 'raw_cons_wtp', 'rw_storage_m3', 'drain_tank_m3', 'aux_ct_basin_m3', 'main_ct_basin_m3', 'dm_prod', 'dm_cons', 'dm_stock_m3_actual', 'fresh_prod', 'fresh_cons', 'fresh_stock_m3', 'swro1_pro_avg', 'swro2_pro_avg', 'swro1_reject', 'swro2_reject', 'bwro1_reject', 'bwro2_reject', 'bwro1_pro_avg', 'bwro2_pro_avg', 'pac', 'poly', 'bio_pre', 'bio_ro', 'cl2_sgr', 'ro_ant', 'naoh', 'hcl', 'tsp', 'o2_sgr', 'nh3', 'biocide', 'bio_acw_kgs', 'tsp_acw_kgs', 'naoh_acw_kgs', 'bio_mcw_kgs', 'ctscale_mcw_kgs', 'pac_wwtp_kgs', 'poly_wwtp_kgs', 'bio_wwtp_kgs'])
            ->map(fn (string $key) => ['key' => $key, 'label' => strtoupper(str_replace('_', ' ', $key))])
            ->all();
    }

    private static function excelPayload(string $filename, string $sheet, array $columns, Collection $rows): array
    {
        return [
            'filename' => $filename,
            'sheets' => [[
                'name' => $sheet,
                'columns' => $columns,
                'rows' => $rows->values(),
            ]],
        ];
    }

    private static function sum(Collection $items, string $field): float
    {
        return round($items->sum(fn (object $item) => (float) ($item->{$field} ?? 0)), 3);
    }

    private static function max(Collection $items, string $field): float
    {
        if ($items->isEmpty()) {
            return 0.0;
        }

        return round((float) $items->max(fn (object $item) => (float) ($item->{$field} ?? 0)), 3);
    }

    private static function avg(Collection $items, string $field): ?float
    {
        $values = $items
            ->pluck($field)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => (float) $value);

        if ($values->isEmpty()) {
            return null;
        }

        return round($values->avg(), 3);
    }

    private static function maxDate(Collection $items, string $field): ?string
    {
        $dates = $items->pluck($field)->filter();

        if ($dates->isEmpty()) {
            return null;
        }

        return CarbonImmutable::parse($dates->max())->toDateTimeString();
    }
}

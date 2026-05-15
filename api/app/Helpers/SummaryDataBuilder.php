<?php

namespace App\Helpers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SummaryDataBuilder
{
    public static function wtp(string $lokasi): array
    {
        $range = self::dateRange();
        $master = DB::table('master_locations')
            ->where('location_code', $lokasi)
            ->first();

        $logs = DB::table('log_sheet_wtp')
            ->where('lokasi', $lokasi)
            ->where('log_date', '>=', $range['start'])
            ->where('log_date', '<', $range['end'])
            ->get();

        $rows = self::dailyRows($logs, 'log_date', [
            'ph', 'cond', 'tds', 'p_alk', 'm_alk', 'th', 'sio2',
        ], ['shift'])
            ->map(fn (array $row) => array_merge([
                'location_code' => $master?->location_code ?? $lokasi,
                'location_name' => $master?->location_name ?? $lokasi,
            ], $row))
            ->values();

        return [
            'info' => self::info($master?->location_code ?? $lokasi, $master?->location_name ?? $lokasi, $rows),
            'rows' => $rows,
            'cards' => [
                self::card('pH', 'ph', $rows, ['min' => $master?->ph_min, 'max' => $master?->ph_max]),
                self::card('Cond', 'cond', $rows, ['max' => $master?->cond_max], 'uS/cm'),
                self::card('TDS', 'tds', $rows, ['max' => $master?->tds_max], 'ppm'),
                self::card('P.Alk', 'p_alk', $rows, ['max' => $master?->p_alk_max], 'ppm'),
                self::card('M.Alk', 'm_alk', $rows, ['max' => $master?->m_alk_max], 'ppm'),
                self::card('T.H', 'th', $rows, ['max' => $master?->th_max], 'ppm'),
                self::card('SiO2', 'sio2', $rows, ['max' => $master?->sio2_max], 'ppm'),
            ],
        ];
    }

    public static function roProcess(string $param): array
    {
        $metric = strtolower($param) === 'cond' ? 'cond' : 'ph';
        $range = self::dateRange();
        $locationCodes = ['PROC01', 'PROC02', 'PROC03', 'PROC04'];
        $masters = DB::table('master_standard_ro_process')
            ->whereIn('loc_id', $locationCodes)
            ->orderBy('loc_id')
            ->get()
            ->keyBy('loc_id');

        $logs = DB::table('log_sheet_ro_process')
            ->whereIn('lokasi', $locationCodes)
            ->where('log_date', '>=', $range['start'])
            ->where('log_date', '<', $range['end'])
            ->get()
            ->groupBy('lokasi');

        $rows = collect($locationCodes)->flatMap(function (string $code) use ($logs, $masters, $metric) {
            $master = $masters->get($code);

            return self::dailyRows($logs->get($code, collect()), 'log_date', [$metric], ['shift'])
                ->map(fn (array $row) => array_merge([
                    'location_code' => $code,
                    'location_name' => $master?->location_name ?? $code,
                ], $row));
        })->values();

        return [
            'info' => [
                'title' => $metric === 'cond' ? 'RO Conductivity Summary' : 'RO pH Summary',
                'subtitle' => 'Trend 7 hari terakhir',
                'last_update' => self::lastUpdate($rows),
                'param' => $metric,
            ],
            'rows' => $rows,
            'cards' => collect($locationCodes)->map(function (string $code) use ($rows, $masters, $metric) {
                $master = $masters->get($code);
                $locationRows = $rows->where('location_code', $code)->values();
                $limit = $metric === 'cond'
                    ? ['max' => $master?->cond_max]
                    : ['min' => $master?->ph_min, 'max' => $master?->ph_max];

                return self::card($master?->location_name ?? $code, $metric, $locationRows, $limit, $metric === 'cond' ? 'uS/cm' : '');
            })->values(),
        ];
    }

    public static function wwtpOl(): array
    {
        $range = self::dateRange();
        $unitName = 'LOC029';
        $logs = DB::table('log_sheet_wwtp_ol')
            ->where('unit_name', $unitName)
            ->where('log_date', '>=', $range['startDate'])
            ->where('log_date', '<=', $range['today'])
            ->get();

        $rows = self::dailyRows($logs, 'log_date', ['cu', 'zn', 'cr', 'tss', 'fe', 'po5'])
            ->map(fn (array $row) => array_merge([
                'location_code' => $unitName,
                'location_name' => 'WWTP - O/L',
            ], $row))
            ->values();

        return [
            'info' => self::info($unitName, 'WWTP - O/L', $rows),
            'rows' => $rows,
            'cards' => [
                self::card('Cu', 'cu', $rows),
                self::card('Zn', 'zn', $rows),
                self::card('Cr', 'cr', $rows),
                self::card('TSS', 'tss', $rows),
                self::card('Fe', 'fe', $rows),
                self::card('PO5', 'po5', $rows),
            ],
        ];
    }

    private static function dateRange(): array
    {
        $today = CarbonImmutable::today();

        return [
            'start' => $today->subDays(6)->startOfDay(),
            'end' => $today->addDay()->startOfDay(),
            'startDate' => $today->subDays(6)->toDateString(),
            'today' => $today->toDateString(),
        ];
    }

    private static function dailyRows(Collection $logs, string $dateField, array $metrics, array $distinctFields = []): Collection
    {
        return $logs
            ->groupBy(fn (object $log) => CarbonImmutable::parse($log->{$dateField})->toDateString())
            ->sortKeys()
            ->map(function (Collection $items, string $date) use ($metrics, $distinctFields) {
                $row = [
                    'date' => $date,
                    'total_rows' => $items->count(),
                    'last_update' => self::maxDate($items, 'log_date') ?? self::maxDate($items, 'input_time'),
                ];

                foreach ($distinctFields as $field) {
                    $row[$field.'_count'] = $items->pluck($field)->filter()->unique()->count();
                }

                foreach ($metrics as $metric) {
                    $row['avg_'.$metric] = self::average($items, $metric);
                }

                return $row;
            })
            ->values();
    }

    private static function info(string $code, string $name, Collection $rows): array
    {
        return [
            'location_code' => $code,
            'location_name' => $name,
            'subtitle' => 'Trend 7 hari terakhir',
            'last_update' => self::lastUpdate($rows),
            'empty' => $rows->isEmpty(),
        ];
    }

    private static function card(string $label, string $metric, Collection $rows, array $limit = [], string $unit = ''): array
    {
        $values = $rows->pluck('avg_'.$metric)->filter(fn ($value) => $value !== null)->values();

        return [
            'label' => $label,
            'metric' => $metric,
            'value' => $values->last(),
            'unit' => $unit,
            'limit' => $limit,
            'trend' => $values,
        ];
    }

    private static function average(Collection $items, string $field): ?float
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
        $dates = $items
            ->pluck($field)
            ->filter()
            ->map(fn ($value) => CarbonImmutable::parse($value));

        if ($dates->isEmpty()) {
            return null;
        }

        return $dates->max()->toDateTimeString();
    }

    private static function lastUpdate(Collection $rows): ?string
    {
        return $rows->pluck('last_update')->filter()->max();
    }
}

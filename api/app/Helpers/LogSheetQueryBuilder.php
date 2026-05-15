<?php

namespace App\Helpers;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class LogSheetQueryBuilder
{
    public static function query(array $definition): Builder
    {
        $table = $definition['table'];
        $query = DB::table($table)->select($table.'.*');

        if (in_array('lokasi', $definition['fields'], true) && isset($definition['locationMaster'])) {
            $master = $definition['locationMaster'];

            $query
                ->leftJoin($master['table'], $table.'.lokasi', '=', $master['table'].'.'.$master['key'])
                ->addSelect($master['table'].'.location_name as location_name');
        }

        return $query;
    }

    public static function qualify(array $definition, string $field): string
    {
        return $definition['table'].'.'.$field;
    }
}

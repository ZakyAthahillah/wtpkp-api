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

        if (in_array('lokasi', $definition['fields'], true)) {
            $query
                ->leftJoin('master_locations', $table.'.lokasi', '=', 'master_locations.location_code')
                ->addSelect('master_locations.location_name as location_name');
        }

        return $query;
    }

    public static function qualify(array $definition, string $field): string
    {
        return $definition['table'].'.'.$field;
    }
}

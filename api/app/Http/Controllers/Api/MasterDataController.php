<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class MasterDataController extends Controller
{
    public function locations(): JsonResponse
    {
        try {
            $locations = DB::table('master_locations')
                ->orderBy('location_code')
                ->get();

            return ApiResponse::success('Data retrieved successfully', $locations);
        } catch (Throwable $exception) {
            report($exception);

            return ApiResponse::error('Failed to retrieve master locations');
        }
    }

    public function standardRo(): JsonResponse
    {
        try {
            $standards = DB::table('master_standard_ro')
                ->orderBy('loc_id')
                ->get();

            return ApiResponse::success('Data retrieved successfully', $standards);
        } catch (Throwable $exception) {
            report($exception);

            return ApiResponse::error('Failed to retrieve master standard RO');
        }
    }

    public function standardRoProcess(): JsonResponse
    {
        try {
            $standards = DB::table('master_standard_ro_process')
                ->orderBy('loc_id')
                ->get();

            return ApiResponse::success('Data retrieved successfully', $standards);
        } catch (Throwable $exception) {
            report($exception);

            return ApiResponse::error('Failed to retrieve master standard RO process');
        }
    }
}

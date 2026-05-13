<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;

class HealthController extends Controller
{
    public function __invoke()
    {
        return ApiResponse::success('API is healthy', [
            'service' => 'wtpkp-api',
            'database' => config('database.default'),
        ]);
    }
}

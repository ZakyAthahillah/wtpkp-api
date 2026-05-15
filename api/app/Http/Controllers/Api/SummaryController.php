<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Helpers\SummaryDataBuilder;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class SummaryController extends Controller
{
    public function wtp(Request $request): JsonResponse
    {
        try {
            $payload = Validator::make($request->query(), [
                'lokasi' => ['nullable', 'string', 'max:10', Rule::exists('master_locations', 'location_code')],
            ])->validate();

            return ApiResponse::success('Data retrieved successfully', SummaryDataBuilder::wtp($payload['lokasi'] ?? 'LOC001'));
        } catch (ValidationException $exception) {
            return ApiResponse::error('Bad request', $exception->errors(), 400);
        } catch (Throwable $exception) {
            report($exception);

            return ApiResponse::error('Failed to retrieve WTP summary');
        }
    }

    public function roProcess(Request $request): JsonResponse
    {
        try {
            $payload = Validator::make($request->query(), [
                'param' => ['nullable', 'string', Rule::in(['pH', 'ph', 'Cond', 'cond'])],
            ])->validate();

            return ApiResponse::success('Data retrieved successfully', SummaryDataBuilder::roProcess($payload['param'] ?? 'pH'));
        } catch (ValidationException $exception) {
            return ApiResponse::error('Bad request', $exception->errors(), 400);
        } catch (Throwable $exception) {
            report($exception);

            return ApiResponse::error('Failed to retrieve RO process summary');
        }
    }

    public function wwtpOl(): JsonResponse
    {
        try {
            return ApiResponse::success('Data retrieved successfully', SummaryDataBuilder::wwtpOl());
        } catch (Throwable $exception) {
            report($exception);

            return ApiResponse::error('Failed to retrieve WWTP O/L summary');
        }
    }
}

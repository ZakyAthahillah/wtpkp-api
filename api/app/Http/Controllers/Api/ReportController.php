<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Helpers\ReportDataBuilder;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class ReportController extends Controller
{
    public function monthlyConsumption(Request $request): JsonResponse
    {
        try {
            $payload = $this->validatedMonth($request);

            return ApiResponse::success('Data retrieved successfully', ReportDataBuilder::monthlyConsumption($payload['month']));
        } catch (ValidationException $exception) {
            return ApiResponse::error('Bad request', $exception->errors(), 400);
        } catch (Throwable $exception) {
            report($exception);

            return ApiResponse::error('Failed to retrieve monthly consumption report');
        }
    }

    public function monthlyWaterAnalysis(Request $request): JsonResponse
    {
        try {
            $payload = $this->validatedMonth($request);

            return ApiResponse::success('Data retrieved successfully', ReportDataBuilder::monthlyWaterAnalysis($payload['month']));
        } catch (ValidationException $exception) {
            return ApiResponse::error('Bad request', $exception->errors(), 400);
        } catch (Throwable $exception) {
            report($exception);

            return ApiResponse::error('Failed to retrieve monthly water analysis report');
        }
    }

    public function dailyWaterAnalysis(Request $request): JsonResponse
    {
        try {
            $payload = Validator::make($request->query(), [
                'date' => ['required', 'date_format:Y-m-d'],
            ])->validate();

            return ApiResponse::success('Data retrieved successfully', ReportDataBuilder::dailyWaterAnalysis($payload['date']));
        } catch (ValidationException $exception) {
            return ApiResponse::error('Bad request', $exception->errors(), 400);
        } catch (Throwable $exception) {
            report($exception);

            return ApiResponse::error('Failed to retrieve daily water analysis report');
        }
    }

    private function validatedMonth(Request $request): array
    {
        return Validator::make($request->query(), [
            'month' => ['required', 'date_format:Y-m'],
        ])->validate();
    }
}

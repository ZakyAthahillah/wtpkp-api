<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Helpers\LogSheetRequestBuilder;
use App\Helpers\LogSheetResourceRegistry;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class LogSheetResourceController extends Controller
{
    public function resources(): JsonResponse
    {
        try {
            $resources = collect(LogSheetResourceRegistry::all())
                ->map(fn (array $definition, string $name) => [
                    'name' => $name,
                    'table' => $definition['table'],
                    'primary_key' => $definition['primaryKey'],
                    'fields' => $definition['fields'],
                ])
                ->values();

            return ApiResponse::success('Data retrieved successfully', $resources);
        } catch (Throwable $exception) {
            report($exception);

            return ApiResponse::error('Failed to retrieve resources');
        }
    }

    public function index(Request $request, string $resource): JsonResponse
    {
        try {
            $definition = LogSheetRequestBuilder::definition($resource);
            $perPage = min(max((int) $request->query('per_page', 15), 1), 100);
            $query = DB::table($definition['table']);

            foreach ($definition['filters'] as $filter) {
                if ($request->filled($filter) && in_array($filter, $definition['fields'], true)) {
                    $query->where($filter, $request->query($filter));
                }
            }

            $rows = $query
                ->orderBy($definition['sort'])
                ->paginate($perPage);

            return ApiResponse::success('Data retrieved successfully', $rows->items(), [
                'current_page' => $rows->currentPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
                'last_page' => $rows->lastPage(),
            ]);
        } catch (ValidationException $exception) {
            return ApiResponse::error('Bad request', $exception->errors(), 400);
        } catch (Throwable $exception) {
            report($exception);

            return ApiResponse::error('Failed to retrieve data');
        }
    }

    public function store(Request $request, string $resource): JsonResponse
    {
        try {
            $definition = LogSheetRequestBuilder::definition($resource);
            $payload = LogSheetRequestBuilder::validatedPayload($request, $definition);
            $payload = LogSheetRequestBuilder::withAuthenticatedOperator($payload, $definition, $request);
            $id = DB::table($definition['table'])->insertGetId($payload, $definition['primaryKey']);
            $row = DB::table($definition['table'])->where($definition['primaryKey'], $id)->first();

            return ApiResponse::success('Data created successfully', $row, null, 201);
        } catch (ValidationException $exception) {
            return ApiResponse::error('Bad request', $exception->errors(), 400);
        } catch (QueryException $exception) {
            report($exception);

            return ApiResponse::error('Failed to create data');
        } catch (Throwable $exception) {
            report($exception);

            return ApiResponse::error('Failed to create data');
        }
    }

    public function show(string $resource, string $id): JsonResponse
    {
        try {
            $definition = LogSheetRequestBuilder::definition($resource);
            $row = DB::table($definition['table'])->where($definition['primaryKey'], $id)->first();

            if (! $row) {
                return ApiResponse::error('Data not found', ['id' => ['The selected data was not found.']], 404);
            }

            return ApiResponse::success('Data retrieved successfully', $row);
        } catch (ValidationException $exception) {
            return ApiResponse::error('Bad request', $exception->errors(), 400);
        } catch (Throwable $exception) {
            report($exception);

            return ApiResponse::error('Failed to retrieve data');
        }
    }

    public function update(Request $request, string $resource, string $id): JsonResponse
    {
        try {
            $definition = LogSheetRequestBuilder::definition($resource);
            $payload = LogSheetRequestBuilder::validatedPayload($request, $definition, true);

            if ($payload === []) {
                return ApiResponse::error('Bad request', ['request' => ['At least one field must be provided for update.']], 400);
            }

            $payload = LogSheetRequestBuilder::withAuthenticatedOperator($payload, $definition, $request);
            $updated = DB::table($definition['table'])->where($definition['primaryKey'], $id)->update($payload);

            if ($updated === 0 && ! DB::table($definition['table'])->where($definition['primaryKey'], $id)->exists()) {
                return ApiResponse::error('Data not found', ['id' => ['The selected data was not found.']], 404);
            }

            $row = DB::table($definition['table'])->where($definition['primaryKey'], $id)->first();

            return ApiResponse::success('Data updated successfully', $row);
        } catch (ValidationException $exception) {
            return ApiResponse::error('Bad request', $exception->errors(), 400);
        } catch (Throwable $exception) {
            report($exception);

            return ApiResponse::error('Failed to update data');
        }
    }

    public function destroy(string $resource, string $id): JsonResponse
    {
        try {
            $definition = LogSheetRequestBuilder::definition($resource);
            $deleted = DB::table($definition['table'])->where($definition['primaryKey'], $id)->delete();

            if ($deleted === 0) {
                return ApiResponse::error('Data not found', ['id' => ['The selected data was not found.']], 404);
            }

            return ApiResponse::success('Data deleted successfully');
        } catch (ValidationException $exception) {
            return ApiResponse::error('Bad request', $exception->errors(), 400);
        } catch (Throwable $exception) {
            report($exception);

            return ApiResponse::error('Failed to delete data');
        }
    }
}

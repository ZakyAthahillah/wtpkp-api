<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\LogSheetResourceController;
use App\Http\Controllers\Api\MasterDataController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('jwt')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/masters/locations', [MasterDataController::class, 'locations']);
    Route::get('/masters/standard-ro', [MasterDataController::class, 'standardRo']);
    Route::get('/masters/standard-ro-process', [MasterDataController::class, 'standardRoProcess']);
    Route::get('/resources', [LogSheetResourceController::class, 'resources']);

    Route::prefix('/log-sheets/{resource}')->group(function () {
        Route::get('/', [LogSheetResourceController::class, 'index']);
        Route::post('/', [LogSheetResourceController::class, 'store']);
        Route::get('/{id}', [LogSheetResourceController::class, 'show']);
        Route::put('/{id}', [LogSheetResourceController::class, 'update']);
        Route::delete('/{id}', [LogSheetResourceController::class, 'destroy']);
    });
});

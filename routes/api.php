<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RepairOrderController;
use App\Http\Controllers\AuthController;

Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::get('repair-orders', [RepairOrderController::class, 'index']);
    Route::get('repair-orders/{repairOrder}', [RepairOrderController::class, 'show']);
    Route::patch('repair-orders/{repairOrder}', [RepairOrderController::class, 'updateStatus']);
}); 
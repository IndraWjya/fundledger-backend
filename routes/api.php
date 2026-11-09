<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

// Public Route
Route::post('/login', [AuthController::class, 'login']);

// Protected Routes (Harus ada Sanctum Token)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Dashboard Metrik
    Route::get('/dashboard/metrics', [DashboardController::class, 'getMetrics']);

    // Transaksi
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions/cash-in', [TransactionController::class, 'storeCashIn']);
    Route::post('/transactions/cash-out', [TransactionController::class, 'storeCashOut']);
    Route::post('/transactions/{id}/approve', [TransactionController::class, 'approveCashOut']);
    Route::post('/transactions/{id}/reject', [TransactionController::class, 'rejectCashOut']);
});
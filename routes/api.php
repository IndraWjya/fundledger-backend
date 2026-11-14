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

    // Organisasi & Anggota
    Route::get('/organization', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        $org = $user->organization;
        $members = \App\Models\User::where('organization_id', $user->organization_id)
            ->select('id', 'name', 'email', 'role', 'created_at')
            ->get();

        return response()->json([
            'id' => $org ? $org->id : 1,
            'name' => $org ? $org->name : 'Organisasi',
            'balance' => $org ? (float) $org->balance : 0,
            'smart_contract_address' => env('SMART_CONTRACT_ADDRESS', null),
            'network' => env('BLOCKCHAIN_NETWORK', 'Lokal EVM / Hardhat (Tahap Setup)'),
            'members_count' => $members->count(),
            'members' => $members->map(function ($m) {
                return [
                    'id' => $m->id,
                    'name' => $m->name,
                    'email' => $m->email,
                    'role' => $m->role,
                    'wallet_address' => null,
                    'joined_at' => $m->created_at ? $m->created_at->format('Y-m-d') : date('Y-m-d'),
                ];
            }),
        ]);
    });

    // Laporan Keuangan Periodik
    Route::get('/reports', [TransactionController::class, 'getReport']);

    // Transaksi
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions/cash-in', [TransactionController::class, 'storeCashIn']);
    Route::post('/transactions/cash-out', [TransactionController::class, 'storeCashOut']);
    Route::post('/transactions/{id}/approve', [TransactionController::class, 'approveCashOut']);
    Route::post('/transactions/{id}/reject', [TransactionController::class, 'rejectCashOut']);
    Route::post('/transactions/reversal', [TransactionController::class, 'storeReversal']);

    // Audit & Verifikasi Hash On-Chain
    Route::get('/audit/verify/{hash}', [TransactionController::class, 'verifyHash']);
});
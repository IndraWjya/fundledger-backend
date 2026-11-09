<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function getMetrics(Request $request)
    {
        $user = $request->user();
        $org = $user->organization;

        // 1. Total Saldo
        $totalBalance = $org ? $org->balance : 0;

        // 2. Pemasukan Bulan Ini (Cash In dengan status completed pada bulan berjalan)
        $monthlyCashIn = Transaction::where('organization_id', $user->organization_id)
            ->where('type', 'cash_in')
            ->where('status', 'completed')
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('amount');

        // 3. Total Transaksi Pending (Cash Out yang menunggu persetujuan)
        $pendingTransactionsCount = Transaction::where('organization_id', $user->organization_id)
            ->where('type', 'cash_out')
            ->where('status', 'pending')
            ->count();

        return response()->json([
            'total_balance'       => (float) $totalBalance,
            'monthly_cash_in'     => (float) $monthlyCashIn,
            'pending_count'       => $pendingTransactionsCount,
        ]);
    }
}
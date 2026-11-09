<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TransactionController extends Controller
{
    // List riwayat transaksi (mendukung filter type dan status)
    public function index(Request $request)
    {
        $query = Transaction::with(['maker:id,name,email', 'checker:id,name,email'])
            ->where('organization_id', $request->user()->organization_id)
            ->latest();

        if ($request->has('type') && in_array($request->type, ['cash_in', 'cash_out'])) {
            $query->where('type', $request->type);
        }

        if ($request->has('status') && in_array($request->status, ['pending', 'completed', 'rejected'])) {
            $query->where('status', $request->status);
        }

        return response()->json($query->get());
    }

    // Catat Cash In (Status langsung completed & tambah balance organisasi)
    public function storeCashIn(Request $request)
    {
        $request->validate([
            'category'    => 'required|string',
            'amount'      => 'required|numeric|min:1',
            'description' => 'required|string',
            'proof'       => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $proofPath = null;
        if ($request->hasFile('proof')) {
            $proofPath = $request->file('proof')->store('proofs', 'public');
        }

        return DB::transaction(function () use ($request, $proofPath) {
            $user = $request->user();

            $transaction = Transaction::create([
                'organization_id' => $user->organization_id,
                'maker_id'        => $user->id,
                'type'            => 'cash_in',
                'category'        => $request->category,
                'amount'          => $request->amount,
                'description'     => $request->description,
                'proof_path'      => $proofPath,
                'status'          => 'completed',
            ]);

            // Tambah balance organisasi
            $user->organization->increment('balance', $request->amount);

            return response()->json([
                'message'     => 'Cash in berhasil dicatat',
                'transaction' => $transaction,
            ], 201);
        });
    }

    // Ajukan Cash Out (Status default: pending)
    public function storeCashOut(Request $request)
    {
        $request->validate([
            'category'    => 'required|string',
            'amount'      => 'required|numeric|min:1',
            'description' => 'required|string',
            'proof'       => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $proofPath = null;
        if ($request->hasFile('proof')) {
            $proofPath = $request->file('proof')->store('proofs', 'public');
        }

        $user = $request->user();

        $transaction = Transaction::create([
            'organization_id' => $user->organization_id,
            'maker_id'        => $user->id,
            'type'            => 'cash_out',
            'category'        => $request->category,
            'amount'          => $request->amount,
            'description'     => $request->description,
            'proof_path'      => $proofPath,
            'status'          => 'pending',
        ]);

        return response()->json([
            'message'     => 'Pengajuan cash out berhasil disimpan (Menunggu persetujuan)',
            'transaction' => $transaction,
        ], 201);
    }

    // Persetujuan Cash Out (Hanya role checker)
    public function approveCashOut(Request $request, $id)
    {
        $user = $request->user();

        if ($user->role !== 'checker') {
            return response()->json(['message' => 'Hanya checker (Ketua) yang berhak menyetujui.'], 403);
        }

        $transaction = Transaction::where('id', $id)
            ->where('organization_id', $user->organization_id)
            ->where('type', 'cash_out')
            ->where('status', 'pending')
            ->firstOrFail();

        return DB::transaction(function () use ($transaction, $user, $request) {
            $org = $user->organization;

            if ($org->balance < $transaction->amount) {
                return response()->json(['message' => 'Saldo organisasi tidak mencukupi.'], 400);
            }

            // Ubah status dan kurangi saldo
            $transaction->update([
                'status'     => 'completed',
                'checker_id' => $user->id,
                'tx_hash'    => $request->input('tx_hash', null), // Opsional: hash dari smart contract jika ada
            ]);

            $org->decrement('balance', $transaction->amount);

            return response()->json([
                'message'     => 'Cash out disetujui dan saldo berhasil dipotong',
                'transaction' => $transaction,
            ]);
        });
    }

    // Tolak Cash Out (Hanya role checker)
    public function rejectCashOut(Request $request, $id)
    {
        $user = $request->user();

        if ($user->role !== 'checker') {
            return response()->json(['message' => 'Hanya checker (Ketua) yang berhak menolak.'], 403);
        }

        $request->validate([
            'rejection_reason' => 'required|string',
        ]);

        $transaction = Transaction::where('id', $id)
            ->where('organization_id', $user->organization_id)
            ->where('type', 'cash_out')
            ->where('status', 'pending')
            ->firstOrFail();

        $transaction->update([
            'status'           => 'rejected',
            'checker_id'       => $user->id,
            'rejection_reason' => $request->rejection_reason,
        ]);

        return response()->json([
            'message'     => 'Pengajuan cash out ditolak',
            'transaction' => $transaction,
        ]);
    }
}
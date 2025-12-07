<?php

namespace App\Http\Controllers;

use App\Models\CashExpense;
use App\Models\CashPayment;
use App\Models\ActivityLog;   
use App\Models\ExpenseRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CashExpenseController extends Controller
{
    /**
     * ACC pengeluaran dari sebuah ExpenseRequest.
     * - Hanya untuk guru (dibatasi di route).
     * - Cek saldo dulu, kalau tidak cukup → error.
     * - Kalau cukup → buat baris di cash_expenses + update expense_requests jadi approved.
     */
      public function approveFromRequest(Request $request, ExpenseRequest $expenseRequest)
    {
        $user = Auth::user();

        // Pastikan hanya boleh ACC jika masih pending
        if ($expenseRequest->status !== 'pending') {
            return redirect()
                ->route('expense-requests.show', $expenseRequest)
                ->with('error', 'Pengajuan ini sudah diproses sebelumnya.');
        }

        // Hitung saldo kas saat ini (berdasarkan tahun ajaran pengajuan)
        $classYearId = $expenseRequest->class_year_id;

        $totalIn = CashPayment::where('class_year_id', $classYearId)->sum('amount');
        $totalOut = CashExpense::where('class_year_id', $classYearId)->sum('amount');
        $currentBalance = $totalIn - $totalOut;

        if ($expenseRequest->amount > $currentBalance) {
            return redirect()
                ->route('expense-requests.show', $expenseRequest)
                ->with('error', 'Saldo kas saat ini tidak mencukupi untuk menyetujui pengajuan ini.');
        }

        DB::transaction(function () use ($expenseRequest, $user, $classYearId) {
            // 🔹 Snapshot sebelum perubahan status
            $before = [
                'status'       => $expenseRequest->status,
                'approved_by'  => $expenseRequest->approved_by,
                'approved_at'  => $expenseRequest->approved_at,
            ];

            // 1) Catat pengeluaran ke tabel cash_expenses
            $cashExpense = CashExpense::create([
                'class_year_id' => $expenseRequest->class_year_id,
                'request_id'    => $expenseRequest->id,
                'date'          => now()->toDateString(), // atau $expenseRequest->request_date jika mau
                'category_id'   => $expenseRequest->category_id,
                'amount'        => $expenseRequest->amount,
                'approved_by'   => $user->id,
                'posted_at'     => now(),
                'description'   => $expenseRequest->description,
            ]);

            // 2) Update status pengajuan
            $expenseRequest->update([
                'status'        => 'approved',
                'approved_by'   => $user->id,
                'approved_at'   => now(),
                'reject_reason' => null,
            ]);

            $expenseRequest->refresh();

            // 3) 🔹 Log: pengajuan disetujui
            ActivityLog::record(
                'expense_request.approved',
                $expenseRequest,
                $classYearId,
                $before,
                [
                    'message'      => 'Pengajuan pengeluaran disetujui',
                    'status'       => $expenseRequest->status,
                    'amount'       => $expenseRequest->amount,
                    'category_id'  => $expenseRequest->category_id,
                    'approved_by'  => $user->id,
                    'approved_at'  => $expenseRequest->approved_at,
                ]
            );

            // 4) 🔹 Log: pengeluaran kas tercatat
            ActivityLog::record(
                'cash_expense.created',
                $cashExpense,
                $classYearId,
                null,
                [
                    'message'      => 'Pengeluaran kas tercatat',
                    'amount'       => $cashExpense->amount,
                    'category_id'  => $cashExpense->category_id,
                    'request_id'   => $cashExpense->request_id,
                    'approved_by'  => $cashExpense->approved_by,
                    'posted_at'    => $cashExpense->posted_at,
                ]
            );
        });

        return redirect()
            ->route('expense-requests.show', $expenseRequest)
            ->with('success', 'Pengajuan berhasil disetujui dan dicatat sebagai pengeluaran kas.');
    }
}

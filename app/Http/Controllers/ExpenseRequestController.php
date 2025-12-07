<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ClassYear;
use App\Models\ExpenseRequest;
use App\Models\CashExpense;
use App\Models\CashPayment;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ExpenseRequestController extends Controller
{
    /**
     * Ambil tahun ajaran aktif.
     */
    protected function getActiveClassYear(): ClassYear
    {
        return ClassYear::where('status', 'active')->firstOrFail();
    }

    public function index(Request $request)
    {
        $classYear = $this->getActiveClassYear();
        $user      = Auth::user();

        $query = ExpenseRequest::with(['category', 'requester'])
            ->forClassYear($classYear->id)
            ->orderByDesc('request_date')
            ->orderByDesc('id');

        // Filter by status (?status=pending/approved/rejected)
        if ($request->filled('status') && in_array($request->status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('status', $request->status);
        }

        $requests = $query->paginate(10)->withQueryString();

        return view('expense_requests.index', compact('requests', 'classYear'));
    }

    /**
     * Form pengajuan pengeluaran (bendahara).
     */
    public function create()
    {
        // View kadang butuh info tahun aktif
        $classYear = $this->getActiveClassYear();

        // Kategori pengeluaran GLOBAL (tidak terikat tahun, tidak pakai is_active/display_order)
        $categories = Category::query()
            ->where('type', 'expense')
            ->orderBy('name')
            ->get();

        if ($categories->isEmpty()) {
            return redirect()
                ->route('expense-requests.index')
                ->with('error', 'Belum ada kategori pengeluaran. Silakan minta guru untuk menambahkan kategori terlebih dahulu.');
        }

        return view('expense_requests.create', compact('categories', 'classYear'));
    }

    /**
     * Simpan pengajuan pengeluaran.
     */
    public function store(Request $request)
    {
       
        $classYear = $this->getActiveClassYear();

        $user      = Auth::user();

        // Validasi
        $validated = $request->validate([
            'request_date' => ['required', 'date'],
            'category_id'  => ['required', 'exists:categories,id'],
            'amount'       => ['required', 'integer', 'min:1'],
            'description'  => ['nullable', 'string', 'max:1000'],
            'nota'         => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ], [
            'request_date.required' => 'Tanggal pengajuan wajib diisi.',
            'request_date.date'     => 'Format tanggal tidak valid.',
            'category_id.required'  => 'Kategori pengeluaran wajib dipilih.',
            'category_id.exists'    => 'Kategori pengeluaran tidak valid.',
            'amount.required'       => 'Nominal pengeluaran wajib diisi.',
            'amount.integer'        => 'Nominal pengeluaran harus berupa angka.',
            'amount.min'            => 'Nominal pengeluaran minimal 1.',
            'description.max'       => 'Deskripsi maksimal 1000 karakter.',
            'nota.file'             => 'File nota tidak valid.',
            'nota.mimes'            => 'Nota hanya boleh berupa file JPG, JPEG, PNG, atau PDF.',
            'nota.max'              => 'Ukuran nota maksimal 2MB.',
        ]);

        // Buat record pengajuan
        $expenseRequest = ExpenseRequest::create([
            'class_year_id' => $classYear->id,
            'request_date'  => $validated['request_date'],
            'category_id'   => $validated['category_id'],
            'amount'        => $validated['amount'],
            'description'   => $validated['description'] ?? null,
            'status'        => 'pending',
            'requested_by'  => $user->id,
        ]);

        // Simpan nota (jika ada)
        if ($request->hasFile('nota')) {
            $file = $request->file('nota');
            $path = $file->store('attachments/expense_requests', 'public');

            $expenseRequest->attachments()->create([
                'file_path' => $path,
                'mime'      => $file->getMimeType(),
                'size'      => $file->getSize(),
            ]);
        }

        // 🔹 Activity log: bendahara mengajukan pengeluaran baru
        ActivityLog::record(
            'expense_request.created',
            $expenseRequest,
            $classYear->id,
            null,
            [
                'message'       => 'Pengajuan pengeluaran dibuat',
                'amount'        => $expenseRequest->amount,
                'category_id'   => $expenseRequest->category_id,
                'requested_by'  => $user->id,
                'request_date'  => $expenseRequest->request_date,
                'status'        => $expenseRequest->status,
            ]
        );

        return redirect()
            ->route('expense-requests.index')
            ->with('success', 'Pengajuan pengeluaran berhasil dibuat dan menunggu persetujuan guru.');
    }

    /**
     * Detail pengajuan (guru & bendahara).
     */
    public function show(ExpenseRequest $expenseRequest)
    {
        $classYear = $this->getActiveClassYear();
        $user      = Auth::user();

        // Pastikan belong ke tahun aktif
        if ($expenseRequest->class_year_id !== $classYear->id) {
            abort(404);
        }

        $expenseRequest->load(['category', 'requester', 'approver', 'attachments']);

        // Hitung saldo kas saat ini (untuk panel ACC guru)
        $totalIn  = CashPayment::where('class_year_id', $classYear->id)->sum('amount');
        $totalOut = CashExpense::where('class_year_id', $classYear->id)->sum('amount');
        $currentBalance = $totalIn - $totalOut;

        return view('expense_requests.show', compact('expenseRequest', 'classYear', 'currentBalance'));
    }

    /**
     * Hapus (batalkan) pengajuan.
     * - Hanya boleh jika status masih pending.
     * - Bendahara hanya boleh hapus pengajuan miliknya.
     */
    public function destroy(ExpenseRequest $expenseRequest)
    {
        $classYear = $this->getActiveClassYear();
        $user      = Auth::user();

        if ($expenseRequest->class_year_id !== $classYear->id) {
            abort(404);
        }

        if ($expenseRequest->status !== 'pending') {
            return redirect()
                ->route('expense-requests.index')
                ->with('error', 'Pengajuan yang sudah diproses tidak dapat dibatalkan.');
        }

        // Bendahara hanya boleh hapus pengajuan miliknya (kalau bukan guru)
        if ($user->hasRole('bendahara') && !$user->hasRole('guru')) {
            if ($expenseRequest->requested_by !== $user->id) {
                abort(403);
            }
        }

        // 🔹 Simpan snapshot sebelum dihapus (supaya masih ada jejaknya)
        $before = [
            'amount'       => $expenseRequest->amount,
            'category_id'  => $expenseRequest->category_id,
            'status'       => $expenseRequest->status,
            'requested_by' => $expenseRequest->requested_by,
            'request_date' => $expenseRequest->request_date,
        ];

        // Hapus file nota kalau ada
        foreach ($expenseRequest->attachments as $attachment) {
            if ($attachment->file_path && Storage::disk('public')->exists($attachment->file_path)) {
                Storage::disk('public')->delete($attachment->file_path);
            }
            $attachment->delete();
        }

        $expenseRequest->delete();

        // 🔹 Activity log: pengajuan dibatalkan
        ActivityLog::record(
            'expense_request.deleted',
            $expenseRequest,
            $classYear->id,
            $before,
            [
                'message' => 'Pengajuan pengeluaran dibatalkan',
            ]
        );

        return redirect()
            ->route('expense-requests.index')
            ->with('success', 'Pengajuan pengeluaran berhasil dibatalkan.');
    }

    /**
     * Guru menolak pengajuan pengeluaran.
     */
    public function reject(Request $request, ExpenseRequest $expenseRequest)
    {
        $user = Auth::user();

        if ($expenseRequest->status !== 'pending') {
            return redirect()
                ->route('expense-requests.show', $expenseRequest)
                ->with('error', 'Pengajuan ini sudah diproses sebelumnya.');
        }

        $validated = $request->validate([
            'reject_reason' => ['required', 'string', 'max:1000'],
        ], [
            'reject_reason.required' => 'Alasan penolakan wajib diisi.',
            'reject_reason.max'      => 'Alasan penolakan maksimal 1000 karakter.',
        ]);

        // 🔹 Snapshot sebelum update (status masih pending)
        $before = [
            'status'        => $expenseRequest->status,
            'reject_reason' => $expenseRequest->reject_reason,
        ];

        $expenseRequest->update([
            'status'        => 'rejected',
            'approved_by'   => $user->id,   // yang menolak tetap dicatat
            'approved_at'   => now(),
            'reject_reason' => $validated['reject_reason'],
        ]);

        $expenseRequest->refresh();

        // 🔹 Activity log: pengajuan ditolak
        ActivityLog::record(
            'expense_request.rejected',
            $expenseRequest,
            $expenseRequest->class_year_id,
            $before,
            [
                'message'       => 'Pengajuan pengeluaran ditolak',
                'status'        => $expenseRequest->status,
                'reject_reason' => $expenseRequest->reject_reason,
                'approved_by'   => $user->id,
                'approved_at'   => $expenseRequest->approved_at,
            ]
        );

        return redirect()
            ->route('expense-requests.show', $expenseRequest)
            ->with('success', 'Pengajuan pengeluaran telah ditolak.');
    }
}

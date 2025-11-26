<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ClassYear;
use App\Models\ExpenseRequest;
use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\CashExpense;
use App\Models\CashPayment;
class ExpenseRequestController extends Controller
{
    /**
     * Ambil tahun ajaran aktif.
     * Sesuaikan dengan cara kamu kalau sudah punya helper sendiri.
     */
    protected function getActiveClassYear(): ClassYear
    {
        return ClassYear::where('status', 'active')->firstOrFail();
    }

    /**
     * Daftar pengajuan pengeluaran.
     * - Bendahara: lihat hanya pengajuan miliknya.
     * - Guru: lihat semua pengajuan di tahun ajaran aktif.
     */
    public function index(Request $request)
    {
        $classYear = $this->getActiveClassYear();
        $user      = Auth::user();

        $query = ExpenseRequest::with(['category', 'requester'])
            ->forClassYear($classYear->id)
            ->orderByDesc('request_date')
            ->orderByDesc('id');

        // Filter by role
        if ($user->hasRole('bendahara') && !$user->hasRole('guru')) {
            $query->where('requested_by', $user->id);
        }

        // Filter by status (optional: ?status=pending/approved/rejected)
        if ($request->filled('status') && in_array($request->status, ['pending', 'approved', 'rejected'])) {
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
        $classYear = $this->getActiveClassYear();

        // Ambil kategori pengeluaran yg aktif
        $categories = Category::where(function ($q) use ($classYear) {
                $q->whereNull('class_year_id')
                  ->orWhere('class_year_id', $classYear->id);
            })
            ->where('type', 'expense')
            ->where('is_active', true)
            ->orderBy('display_order')
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

        // Buat record expense_requests
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
            $file     = $request->file('nota');
            $path     = $file->store('attachments/expense_requests', 'public');
            $mime     = $file->getMimeType();
            $size     = $file->getSize();

            $expenseRequest->attachments()->create([
                'file_path' => $path,
                'mime'      => $mime,
                'size'      => $size,
            ]);
        }

        // (Opsional) tulis activity log di Task 13

        return redirect()
            ->route('expense-requests.index')
            ->with('success', 'Pengajuan pengeluaran berhasil dibuat dan menunggu persetujuan guru.');
    }

    /**
     * Detail pengajuan.
     */
     public function show(ExpenseRequest $expenseRequest)
{
    $classYear = $this->getActiveClassYear();
    $user      = Auth::user();

    if ($expenseRequest->class_year_id !== $classYear->id) {
        abort(404);
    }

    // Bendahara cuma boleh lihat pengajuan miliknya
    if ($user->hasRole('bendahara') && !$user->hasRole('guru')) {
        if ($expenseRequest->requested_by !== $user->id) {
            abort(403);
        }
    }

    $expenseRequest->load(['category', 'requester', 'approver', 'attachments']);

    // Hitung saldo kas sekarang (biar guru bisa lihat sebelum ACC)
    $totalIn  = CashPayment::where('class_year_id', $classYear->id)->sum('amount');
    $totalOut = CashExpense::where('class_year_id', $classYear->id)->sum('amount');
    $currentBalance = $totalIn - $totalOut;

    return view('expense_requests.show', compact('expenseRequest', 'classYear', 'currentBalance'));
}


    /**
     * Hapus (batalkan) pengajuan.
     * - Hanya boleh jika status masih pending.
     * - Bendahara hanya boleh hapus pengajuan miliknya.
     * - Guru boleh menghapus jika diinginkan (opsional).
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

        // Bendahara hanya boleh hapus pengajuan miliknya
        if ($user->hasRole('bendahara') && !$user->hasRole('guru')) {
            if ($expenseRequest->requested_by !== $user->id) {
                abort(403);
            }
        }

        // Hapus file nota kalau ada
        foreach ($expenseRequest->attachments as $attachment) {
            if ($attachment->file_path && Storage::disk('public')->exists($attachment->file_path)) {
                Storage::disk('public')->delete($attachment->file_path);
            }
            $attachment->delete();
        }

        $expenseRequest->delete();

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

    $expenseRequest->update([
        'status'        => 'rejected',
        'approved_by'   => $user->id,    // yang menolak tetap dicatat di sini
        'approved_at'   => now(),
        'reject_reason' => $validated['reject_reason'],
    ]);

    // TODO: Activity log

    return redirect()
        ->route('expense-requests.show', $expenseRequest)
        ->with('success', 'Pengajuan pengeluaran telah ditolak.');
}

}

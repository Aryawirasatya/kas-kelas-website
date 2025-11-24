<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\CashService;
use App\Models\ClassYear;
use App\Models\CashPeriod;

/**
 * Kelola "Alasan Belum Lunas" untuk periode berjalan.
 *
 * Aturan:
 * - Alasan hanya bisa dibuat/diubah saat ada PERIODE OPEN (minggu berjalan).
 * - Disimpan satu baris per (class_year_id, period_id, enrollment_id).
 * - Menggunakan CashService::upsertReason() agar konsisten (kalau reason kosong, data dihapus).
 */
class UnpaidReasonController extends Controller
{
    public function __construct(private CashService $svc)
    {
        // Jika perlu, lindungi dengan middleware role:
        // $this->middleware(['role:guru|bendahara']);
    }

    /**
     * Simpan alasan untuk siswa pada PERIODE OPEN saat ini.
     * Request fields:
     * - enrollment_id: id pada tabel student_enrollments
     * - reason       : teks alasan (1..255)
     */
    public function store(Request $req)
    {
        $req->validate([
            'enrollment_id' => ['required', 'integer', 'exists:student_enrollments,id'],
            'reason'        => ['required', 'string', 'max:255'],
        ]);

        // Tahun aktif & periode OPEN
        $year   = ClassYear::active()->latest('id')->firstOrFail();
        $period = $this->svc->getOpenPeriod($year);
        if (!$period) {
            return back()->withErrors(['reason' => 'Tidak ada periode OPEN.']);
        }

        DB::transaction(function () use ($req, $year, $period) {
            $this->svc->upsertReason(
                (int)$year->id,
                (int)$period->id,
                (int)$req->integer('enrollment_id'),
                trim((string)$req->input('reason')),
                Auth::id()
            );
        });

        return back()->with('success', 'Alasan tersimpan.');
    }

    /**
     * Perbarui alasan pada PERIODE OPEN (semantik sama seperti store).
     * Kamu bisa pakai route PUT/PATCH ke endpoint yang sama.
     */
    public function update(Request $req)
    {
        // Supaya kuat, tetap validasi exists & panjang reason
        $req->validate([
            'enrollment_id' => ['required', 'integer', 'exists:student_enrollments,id'],
            'reason'        => ['required', 'string', 'max:255'],
        ]);

        // Reuse store: tetap memastikan periode OPEN
        return $this->store($req);
    }

    /**
     * (Opsional) Hapus alasan untuk siswa di PERIODE OPEN.
     * Kalau kamu butuh tombol "Hapus Alasan", arahkan ke method ini.
     *
     * Request fields:
     * - enrollment_id: id pada tabel student_enrollments
     */
    public function destroy(Request $req)
    {
        $req->validate([
            'enrollment_id' => ['required', 'integer', 'exists:student_enrollments,id'],
        ]);

        $year   = ClassYear::active()->latest('id')->firstOrFail();
        $period = $this->svc->getOpenPeriod($year);
        if (!$period) {
            return back()->withErrors(['reason' => 'Tidak ada periode OPEN.']);
        }

        DB::transaction(function () use ($req, $year, $period) {
            // Kirim reason kosong → CashService akan menghapus baris existing
            $this->svc->upsertReason(
                (int)$year->id,
                (int)$period->id,
                (int)$req->integer('enrollment_id'),
                '', // kosongkan → hapus
                Auth::id()
            );
        });

        return back()->with('success', 'Alasan dihapus.');
    }
}

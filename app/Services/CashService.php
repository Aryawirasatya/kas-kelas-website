<?php

namespace App\Services;

use App\Models\CashPayment;
use App\Models\CashPeriod;
use App\Models\ClassYear;
use App\Models\StudentEnrollment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CashService
{
    public function getActiveYear(): ?ClassYear
    {
        return ClassYear::with('setting')->active()->latest('id')->first();
    }

    public function getOpenPeriod(?ClassYear $year): ?CashPeriod
    {
        if (!$year) {
            return null;
        }

        return $year->periods()->where('status', 'open')->first();
    }

    public function nominal(?ClassYear $year): int
    {
        return (int) ($year?->setting?->kas_nominal ?? 0);
    }

    /** daftar siswa aktif */
    public function activeEnrollments(ClassYear $year)
    {
        return StudentEnrollment::with('user')
            ->where('class_year_id', $year->id)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();
    }

    /**
     * TOTAL NETTO yang sudah dibayar untuk 1 enrollment di 1 periode:
     *  net = direct + alloc_in - alloc_out
     *
     * - direct   : pembayaran yang period_id-nya = periode ini
     * - alloc_in : alokasi dari pembayaran minggu lain yang DIARAHKAN ke periode ini
     * - alloc_out: alokasi dari pembayaran di periode ini yang dipakai untuk periode lain
     */
    public function totalPaidForEnrollment(CashPeriod $period, int $enrollmentId): int
    {
        $yearId   = (int) $period->class_year_id;
        $periodId = (int) $period->id;

        // 1) Pembayaran langsung di periode ini
        $direct = (int) DB::table('cash_payments')
            ->where('class_year_id', $yearId)
            ->where('period_id', $periodId)
            ->where('enrollment_id', $enrollmentId)
            ->sum('amount');

        // 2) Alokasi MASUK ke periode ini dari pembayaran minggu lain
        $allocIn = (int) DB::table('arrear_allocations as a')
            ->join('cash_payments as p', 'p.id', '=', 'a.payment_id')
            ->where('p.class_year_id', $yearId)
            ->where('p.enrollment_id', $enrollmentId)
            ->where('a.period_id', $periodId)   // target periode = periode ini
            ->sum('a.allocated_amount');

        // 3) Alokasi KELUAR dari pembayaran di periode ini untuk periode lain
        $allocOut = (int) DB::table('arrear_allocations as a')
            ->join('cash_payments as p', 'p.id', '=', 'a.payment_id')
            ->where('p.class_year_id', $yearId)
            ->where('p.enrollment_id', $enrollmentId)
            ->where('p.period_id', $periodId)       // pembayaran dibuat di periode ini
            ->where('a.period_id', '<>', $periodId) // tapi dialokasikan ke periode lain
            ->sum('a.allocated_amount');

        $net = $direct + $allocIn - $allocOut;

        return max(0, $net);
    }

    public function statusForStudent(int $nominal, int $totalNet): string
    {
        return $totalNet >= $nominal && $nominal > 0 ? 'LUNAS' : 'BELUM';
    }

    /** ambil alasan belum lunas (jika ada) */
    public function reasonFor(int $periodId, int $enrollmentId): ?string
    {
        return DB::table('unpaid_reasons')
            ->where('period_id', $periodId)
            ->where('enrollment_id', $enrollmentId)
            ->value('reason');
    }

    /**
     * Upsert alasan belum lunas. Periode HARUS OPEN (agar sesuai aturan input saat minggu berjalan).
     * Kolom wajib di tabel unpaid_reasons: class_year_id, period_id, enrollment_id, reason, set_by, timestamps
     */
    public function upsertReason(
        int $yearId,
        int $periodId,
        int $enrollmentId,
        ?string $reason,
        ?int $userId = null
    ): void {
        DB::transaction(function () use ($yearId, $periodId, $enrollmentId, $reason, $userId) {
            $active = ClassYear::active()->latest('id')->first();
            if (!$active || (int) $active->id !== (int) $yearId) {
                throw new \RuntimeException('Tahun aktif tidak cocok.');
            }

            $period = CashPeriod::where('id', $periodId)
                ->where('class_year_id', $yearId)
                ->first();

            if (!$period || $period->status !== 'open') {
                throw new \RuntimeException('Periode tidak OPEN.');
            }

            $enrollOk = StudentEnrollment::where('id', $enrollmentId)
                ->where('class_year_id', $yearId)
                ->where('is_active', true)
                ->exists();

            if (!$enrollOk) {
                throw new \RuntimeException('Enrollment tidak valid/aktif di tahun ini.');
            }

            $reason = trim((string) $reason);
            if ($reason === '') {
                DB::table('unpaid_reasons')
                    ->where('period_id', $periodId)
                    ->where('enrollment_id', $enrollmentId)
                    ->delete();

                return;
            }

            $now   = now();
            $setBy = $userId ?? Auth::id();

            $exists = DB::table('unpaid_reasons')
                ->where('period_id', $periodId)
                ->where('enrollment_id', $enrollmentId)
                ->first();

            if ($exists) {
                DB::table('unpaid_reasons')
                    ->where('period_id', $periodId)
                    ->where('enrollment_id', $enrollmentId)
                    ->update([
                        'reason'     => $reason,
                        'set_by'     => $setBy,
                        'updated_at' => $now,
                    ]);
            } else {
                DB::table('unpaid_reasons')->insert([
                    'class_year_id' => $yearId,
                    'period_id'     => $periodId,
                    'enrollment_id' => $enrollmentId,
                    'reason'        => $reason,
                    'set_by'        => $setBy,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);
            }
        });
    }

    /**
     * Sisa kewajiban untuk 1 siswa di 1 periode:
     *  remaining = weeklyNominal - totalPaidForEnrollment(period, enrollmentId)
     */
    public function remainingFor(int $periodId, int $enrollmentId, int $weeklyNominal): int
    {
        if ($weeklyNominal <= 0) {
            return 0;
        }

        $period = CashPeriod::find($periodId);
        if (!$period) {
            return 0;
        }

        $net = $this->totalPaidForEnrollment($period, $enrollmentId);

        return max(0, $weeklyNominal - $net);
    }

    /**
     * Ambil daftar periode tunggakan (berurutan dari paling tua),
     * hanya yang masih punya sisa > 0.
     *
     * PERHATIAN: sekarang pakai remainingFor() yang sudah aware alokasi.
     */
    public function arrearsPeriods(
        int $classYearId,
        int $enrollmentId,
        int $weeklyNominal,
        ?int $excludePeriodId = null
    ) {
        // Ambil semua periode tahun ini, urut week_no
        $periods = DB::table('cash_periods')
            ->select('id', 'week_no', 'date_start', 'date_end', 'status')
            ->where('class_year_id', $classYearId)
            ->orderBy('week_no')
            ->get();

        // Hitung sisa tiap period
        $result = collect();
        foreach ($periods as $p) {
            if ($excludePeriodId && (int) $p->id === (int) $excludePeriodId) {
                // skip misalnya minggu berjalan
                continue;
            }

            $remain = $this->remainingFor((int) $p->id, $enrollmentId, $weeklyNominal);
            if ($remain > 0) {
                $p->remaining = $remain;
                $result->push($p);
            }
        }

        return $result; // Collection of stdClass {id, week_no, date_start, date_end, status, remaining}
    }

    /** util: insert satu baris payment apa adanya */
    public function addPaymentRow(
        int $classYearId,
        int $periodId,
        int $enrollmentId,
        int $amount,
        string $date,
        ?string $note,
        ?int $userId = null
    ): void {
        DB::table('cash_payments')->insert([
            'class_year_id' => $classYearId,
            'period_id'     => $periodId,
            'enrollment_id' => $enrollmentId,
            'amount'        => $amount,
            'date'          => $date,
            'note'          => $note,
            'received_by'   => $userId ?? Auth::id(),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    /**
     * createPayment / updatePayment / deletePayment
     * masih disimpan kalau nanti mau dipakai lagi.
     * Logika utama auto-tunggakan sekarang ada di CashController (arrear_allocations).
     */

    /** create payment (lama; dipakai kalau mau force ke minggu OPEN saja) */
    public function createPayment(array $data): CashPayment
    {
        return DB::transaction(function () use ($data) {
            $year   = ClassYear::active()->latest('id')->firstOrFail();
            $period = $this->getOpenPeriod($year);
            if (!$period) {
                throw new \RuntimeException('Tidak ada periode OPEN. Minta guru membuka periode.');
            }

            $enroll = StudentEnrollment::where('class_year_id', $year->id)
                ->where('id', $data['enrollment_id'])
                ->where('is_active', true)
                ->first();

            if (!$enroll) {
                throw new \RuntimeException('Siswa (enrollment) tidak valid/aktif pada tahun ini.');
            }

            return CashPayment::create([
                'class_year_id' => $year->id,
                'period_id'     => $period->id,
                'enrollment_id' => $data['enrollment_id'],
                'amount'        => (int) $data['amount'],
                'date'          => Carbon::parse($data['date'] ?? now())->toDateString(),
                'note'          => $data['note'] ?? null,
                'received_by'   => Auth::id(),
            ]);
        });
    }

    /** update payment (guard: periode OPEN) */
    public function updatePayment(CashPayment $payment, array $data): CashPayment
    {
        return DB::transaction(function () use ($payment, $data) {
            $year = ClassYear::active()->latest('id')->firstOrFail();
            if ((int) $payment->class_year_id !== (int) $year->id) {
                throw new \RuntimeException('Transaksi tidak termasuk tahun aktif.');
            }

            $period = $payment->period()->first();
            if (!$period || $period->status !== 'open') {
                throw new \RuntimeException('Periode sudah CLOSED. Tidak bisa mengubah pembayaran.');
            }

            $payment->update([
                'amount' => (int) $data['amount'],
                'date'   => Carbon::parse($data['date'] ?? now())->toDateString(),
                'note'   => $data['note'] ?? null,
            ]);

            return $payment;
        });
    }

    /** hapus payment (guard: periode OPEN) */
    public function deletePayment(CashPayment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $year = ClassYear::active()->latest('id')->firstOrFail();
            if ((int) $payment->class_year_id !== (int) $year->id) {
                throw new \RuntimeException('Transaksi tidak termasuk tahun aktif.');
            }

            $period = $payment->period()->first();
            if (!$period || $period->status !== 'open') {
                throw new \RuntimeException('Periode sudah CLOSED. Tidak bisa menghapus pembayaran.');
            }

            $payment->delete();
        });
    }

    /**
     * Breakdown untuk satu siswa di satu periode:
     * - direct    : bayar langsung di minggu ini
     * - alloc_in  : bagian dari pembayaran minggu lain yang dialokasikan ke minggu ini
     * - alloc_out : bagian dari pembayaran minggu ini yang dialokasikan ke minggu lain
     * - net       : nilai bersih yang dihitung untuk status minggu ini
     *
     * Ini kepakai kalau nanti kamu mau tampilkan breakdown di UI lain.
     */
    public function breakdownForEnrollment(CashPeriod $period, int $enrollmentId): array
    {
        $yearId   = (int) $period->class_year_id;
        $periodId = (int) $period->id;

        // 1) Direct payment di periode ini
        $direct = (int) DB::table('cash_payments')
            ->where('class_year_id', $yearId)
            ->where('period_id', $periodId)
            ->where('enrollment_id', $enrollmentId)
            ->sum('amount');

        // 2) Allocation IN ke periode ini dari minggu lain
        $allocIn = (int) DB::table('arrear_allocations as a')
            ->join('cash_payments as p', 'p.id', '=', 'a.payment_id')
            ->where('p.class_year_id', $yearId)
            ->where('p.enrollment_id', $enrollmentId)
            ->where('a.period_id', $periodId)
            ->sum('a.allocated_amount');

        // 3) Allocation OUT dari periode ini ke minggu lain
        $allocOut = (int) DB::table('arrear_allocations as a')
            ->join('cash_payments as p', 'p.id', '=', 'a.payment_id')
            ->where('p.class_year_id', $yearId)
            ->where('p.enrollment_id', $enrollmentId)
            ->where('p.period_id', $periodId)
            ->where('a.period_id', '<>', $periodId)
            ->sum('a.allocated_amount');

        $net = max(0, $direct + $allocIn - $allocOut);

        return [
            'direct'    => $direct,
            'alloc_in'  => $allocIn,
            'alloc_out' => $allocOut,
            'net'       => $net,
        ];
    }
}

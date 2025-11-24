<?php

namespace App\Services;

use App\Models\CashPeriod;
use App\Models\ClassYear;
use App\Models\StudentEnrollment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PeriodService
{
    /**
     * Ambil tahun ajaran aktif terakhir.
     */
    public function getActiveYear(): ?ClassYear
    {
        return ClassYear::active()->latest('id')->first();
    }

    /**
     * Daftar periode untuk 1 tahun ajaran, urut dari paling awal.
     */
    public function listForYear(?ClassYear $year)
    {
        if (!$year) {
            return collect();
        }

        return $year->periods()
            ->orderBy('date_start')
            ->get();
    }

    /**
     * Ambil periode OPEN (jika ada) untuk tahun ajaran tertentu.
     */
    public function getOpenPeriod(?ClassYear $year): ?CashPeriod
    {
        if (!$year) {
            return null;
        }

        return $year->periods()
            ->where('status', 'open')
            ->orderBy('date_start', 'desc')
            ->first();
    }

    /**
     * Alias ke getOpenPeriod (biar kompatibel dengan kode lama).
     */
    public function currentOpen(ClassYear $year): ?CashPeriod
    {
        return $this->getOpenPeriod($year);
    }

    /**
     * Guard: pastikan tidak ada > 1 periode OPEN pada tahun ini.
     */
    public function ensureSingleOpen(ClassYear $year): void
    {
        $count = $year->periods()->where('status', 'open')->count();
        if ($count > 1) {
            throw new \RuntimeException("Terdapat lebih dari satu periode 'open' pada tahun ajaran {$year->academic_year}.");
        }
    }

    /**
     * Buat 1 periode minggu baru (manual) untuk tahun ajaran tertentu.
     *
     * - week_no = max(week_no) + 1
     * - date_start & date_end sesuai parameter
     * - status = 'open' kalau $openImmediately = true
     *
     * Guard:
     * - Tidak boleh overlap tanggal dengan periode lain di tahun ini
     * - Kalau $openImmediately = true → tidak boleh ada periode lain yang masih OPEN
     */
    public function createOneWeek(ClassYear $year, Carbon $start, Carbon $end, bool $openImmediately = true): CashPeriod
    {
        $startDate = $start->toDateString();
        $endDate   = $end->toDateString();

        // Cek overlap tanggal dengan periode lain
        $hasOverlap = CashPeriod::where('class_year_id', $year->id)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('date_start', [$startDate, $endDate])
                  ->orWhereBetween('date_end', [$startDate, $endDate])
                  ->orWhere(function ($q2) use ($startDate, $endDate) {
                      $q2->where('date_start', '<=', $startDate)
                         ->where('date_end', '>=', $endDate);
                  });
            })
            ->exists();

        if ($hasOverlap) {
            throw new \RuntimeException('Rentang tanggal minggu baru bertabrakan dengan periode lain.');
        }

        // Kalau mau langsung OPEN, pastikan tidak ada OPEN lain
        if ($openImmediately) {
            $currentOpen = $this->getOpenPeriod($year);
            if ($currentOpen) {
                throw new \RuntimeException('Masih ada periode OPEN lain. Tutup dulu sebelum membuat yang baru.');
            }
        }

        return DB::transaction(function () use ($year, $start, $end, $openImmediately) {
            $maxWeekNo = CashPeriod::where('class_year_id', $year->id)->max('week_no');
            $weekNo    = ((int) $maxWeekNo) + 1;

            $period = new CashPeriod();
            $period->class_year_id = $year->id;
            $period->year          = (int) $start->year;
            $period->month         = (int) $start->month;
            $period->week_no       = $weekNo;
            $period->date_start    = $start->toDateString();
            $period->date_end      = $end->toDateString();
            $period->status        = $openImmediately ? 'open' : 'closed';
            $period->save();

            return $period;
        });
    }

    /**
     * Buka 1 periode:
     * - Tutup semua periode OPEN lain di tahun yang sama
     * - Set status periode ini = 'open'
     */
    public function open(ClassYear $year, CashPeriod $period): void
    {
        // Pastikan periode ini milik tahun yang sama
        if ((int)$period->class_year_id !== (int)$year->id) {
            throw new \RuntimeException('Periode tidak termasuk tahun ajaran ini.');
        }

        DB::transaction(function () use ($year, $period) {
            // Tutup semua yang masih OPEN di tahun ini (guard)
            $year->periods()
                ->where('status', 'open')
                ->update(['status' => 'closed']);

            // Buka periode target
            $period->update(['status' => 'open']);
        });

        // Double-check cuma ada 1 OPEN
        $this->ensureSingleOpen($year);
    }

    /**
     * Tutup periode (status = 'closed').
     */
    public function close(CashPeriod $period): CashPeriod
    {
        $period->status = 'closed';
        $period->save();

        return $period;
    }

    /**
     * DAFTAR siswa yang BELUM LUNAS di suatu periode dan TIDAK punya alasan.
     *
     * Dipakai saat mau menutup periode: kalau hasilnya kosong → aman ditutup.
     */
    public function unpaidWithoutReason(int $classYearId, int $periodId, int $weeklyNominal)
    {
        // Ambil enrollment aktif + user
        $enrolls = StudentEnrollment::with('user')
            ->where('class_year_id', $classYearId)
            ->where('is_active', true)
            ->get();

        if ($enrolls->isEmpty() || $weeklyNominal <= 0) {
            return collect(); // tidak ada kewajiban → aman
        }

        // Total bayar per enrollment di periode tersebut
        $paidByEnroll = DB::table('cash_payments')
            ->select('enrollment_id', DB::raw('SUM(amount) as total'))
            ->where('period_id', $periodId)
            ->groupBy('enrollment_id')
            ->pluck('total', 'enrollment_id'); // [enrollment_id => total]

        // Alasan per enrollment di periode tersebut
        $reasonByEnroll = DB::table('unpaid_reasons')
            ->where('period_id', $periodId)
            ->pluck('reason', 'enrollment_id'); // [enrollment_id => reason]

        // Filter yang belum lunas & reason kosong
        $missing = $enrolls->filter(function ($en) use ($paidByEnroll, $reasonByEnroll, $weeklyNominal) {
            $paid  = (int) ($paidByEnroll[$en->id] ?? 0);
            $lunas = $paid >= $weeklyNominal;
            if ($lunas) {
                return false;
            }

            $reason = (string) ($reasonByEnroll[$en->id] ?? '');
            return trim($reason) === '';
        });

        return $missing->values();
    }
}

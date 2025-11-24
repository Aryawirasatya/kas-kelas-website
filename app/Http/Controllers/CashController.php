<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Models\CashPayment;
use App\Services\CashService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashController extends Controller
{
    public function __construct(private CashService $svc) {}

    /** Halaman utama kas mingguan (bendahara) */
    public function index(Request $request)
    {
        $year    = $this->svc->getActiveYear();
        $period  = $this->svc->getOpenPeriod($year);
        $nominal = $this->svc->nominal($year);

        // daftar siswa aktif (enrollments + user)
        $enrolls = $year ? $this->svc->activeEnrollments($year) : collect();

        // hitung total & status per siswa (untuk periode OPEN saat ini)
        $rows = $enrolls->map(function ($en) use ($period, $nominal) {
            $total  = $period ? $this->svc->totalPaidForEnrollment($period, $en->id) : 0;
            $status = $period ? $this->svc->statusForStudent($nominal, $total) : 'BELUM';
            $sisa   = max(0, $nominal - $total);
            $reason = $period ? $this->svc->reasonFor($period->id, $en->id) : null;

            return (object) [
                'enrollment' => $en,
                'user'       => $en->user,
                'total'      => $total,
                'status'     => $status,
                'sisa'       => $sisa,
                'reason'     => $reason,
            ];
        });

        // filter ?only=lunas|belum
        if ($request->filled('only')) {
            $only = strtolower((string) $request->query('only'));
            if (in_array($only, ['lunas', 'belum'], true)) {
                $rows = $rows->filter(fn ($r) => strtolower($r->status) === $only);
            }
        }

        // ringkasan rekap periode berjalan
        $rekap = [
            'total_siswa' => $rows->count(),
            'lunas'       => $rows->where('status', 'LUNAS')->count(),
            'belum'       => $rows->where('status', 'BELUM')->count(),
            'terkumpul'   => $period
                ? (int) DB::table('cash_payments')->where('period_id', $period->id)->sum('amount')
                : 0,
                
        ];

        // Riwayat pembayaran minggu ini -> groupBy enrollment_id
        $paymentsByStudent = collect();
        if ($period) {
            $paymentsByStudent = CashPayment::with('receiver')
                ->where('period_id', $period->id)
                ->orderByDesc('date')
                ->get()
                ->groupBy('enrollment_id');
        }

        // Kas fisik MURNI masuk minggu ini per siswa (tanpa lihat alokasi tunggakan)
        $rawPaidByStudent = collect();
        if ($period) {
            $rawPaidByStudent = DB::table('cash_payments')
                ->select('enrollment_id', DB::raw('SUM(amount) as total'))
                ->where('period_id', $period->id)
                ->groupBy('enrollment_id')
                ->pluck('total', 'enrollment_id'); // [enrollment_id => total]
        }

        // Alokasi tunggakan per pembayaran (untuk detail di modal Riwayat)
        $allocationsByPayment = collect();
        if ($period) {
            $paymentIds = DB::table('cash_payments')
                ->where('period_id', $period->id)
                ->pluck('id');

            if ($paymentIds->isNotEmpty()) {
                $allocationsByPayment = DB::table('arrear_allocations as a')
                    ->join('cash_periods as per', 'per.id', '=', 'a.period_id')
                    ->select(
                        'a.payment_id',
                        'a.allocated_amount',
                        'a.period_id',
                        'per.week_no',
                        'per.date_start',
                        'per.date_end'
                    )
                    ->whereIn('a.payment_id', $paymentIds)
                    ->orderBy('per.date_start')
                    ->get()
                    ->groupBy('payment_id'); // [payment_id => Collection(rows)]
            }
        }

        // ====== DATA TUNGGAKAN UNTUK MODAL (oldest-first) ======
        $arrearsByStudent = [];
        if ($year && $period && $nominal > 0) {
            $arrearsByStudent = $this->computeArrearsMap(
                (int) $year->id,
                (int) $period->id,
                (int) $nominal
            );
        }

        // total tunggakan semua siswa (untuk KPI di header)
        $tunggakanTotal = 0;
        if (!empty($arrearsByStudent)) {
            $tunggakanTotal = collect($arrearsByStudent)
                ->flatMap(fn ($items) => $items) // gabung semua collection per siswa
                ->sum('sisa');                   // pakai field 'sisa' dari getArrearsForEnrollment()
        }

        $rekap['tunggakan_total'] = (int) $tunggakanTotal;


        // Blade kadang cek $lateOptions atau $arrearsByStudent -> kita set alias
        $lateOptions = $arrearsByStudent;

        return view('cash.index', compact(
            'year',
            'period',
            'nominal',
            'rows',
            'rekap',
            'paymentsByStudent',
            'arrearsByStudent',
            'lateOptions',
            'rawPaidByStudent',
            'allocationsByPayment'
        ));
    }

    /** Simpan pembayaran baru + auto alokasi tunggakan (oldest-first) */
    public function store(StorePaymentRequest $req)
    {
        // Ambil scalar dari attributes (diset oleh FormRequest)
        $periodId     = (int) $req->attributes->get('_period_id');       // periode OPEN yang mencakup tanggal input
        $classYearId  = (int) $req->attributes->get('_class_year_id');
        $enrollmentId = (int) $req->attributes->get('_enrollment_id');

        // Double check period OPEN
        $period = DB::table('cash_periods')->where('id', $periodId)->first();
        if (!$period || $period->status !== 'open') {
            return back()->withErrors(['date' => 'Periode sudah ditutup.']);
        }

        // Opsi dari UI
        $targetPeriodId = (int) ($req->input('target_period_id') ?: 0); // radio "Periode Pembayaran" di UI
        // checkbox lama (masih diterima agar tidak merusak form lama)
        $autoSplit    = (bool) $req->boolean('auto_split');
        $allocArrears = (bool) $req->boolean('alloc_arrears');

        DB::transaction(function () use ($req, $period, $classYearId, $enrollmentId, $targetPeriodId, $autoSplit, $allocArrears) {

            // 1) Simpan pembayaran DI PERIODE OPEN (konsisten & mudah audit)
            $paymentId = DB::table('cash_payments')->insertGetId([
                'class_year_id' => $classYearId,
                'period_id'     => $period->id, // selalu dicatat di minggu OPEN
                'enrollment_id' => $enrollmentId,
                'amount'        => (int) $req->amount,
                'date'          => $req->date,
                'note'          => $req->note,
                'received_by'   => auth()->id(),
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            // Ambil nominal & yearId utk hitung tunggakan
            $yearId  = (int) DB::table('cash_periods')->where('id', $period->id)->value('class_year_id');
            $nominal = (int) DB::table('class_settings')->where('class_year_id', $yearId)->value('kas_nominal');

            $left = (int) $req->amount;

            // Helper untuk alokasi baris ke periode tertentu
            $alloc = function (int $toPeriodId, int $amount) use ($paymentId) {
                DB::table('arrear_allocations')->insert([
                    'payment_id'       => $paymentId,
                    'period_id'        => $toPeriodId,
                    'allocated_amount' => $amount,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            };

            // ========= 2) Strategi alokasi =========
            // LANGKAH 1: cari tunggakan lama (periode != current), oldest-first
            $arrears = collect();
            if ($nominal > 0) {
                $arrears = $this->getArrearsForEnrollment(
                    $enrollmentId,
                    $yearId,
                    (int) $period->id,
                    $nominal
                ); // collection of {period_id, sisa, ...}
            }

            // a) SELALU auto-split ke tunggakan lama dulu kalau masih ada
            if ($left > 0 && $arrears->isNotEmpty()) {
                foreach ($arrears as $a) {
                    if ($left <= 0) {
                        break;
                    }

                    $need = (int) max(0, $a->sisa);
                    if ($need <= 0) {
                        continue;
                    }

                    $take = min($left, $need);
                    $alloc((int) $a->period_id, $take);
                    $left -= $take;
                }
            }

            // b) (OPSIONAL) sisa uang boleh dialokasikan ke 1 periode spesifik (kalau masih pakai radio target_period_id)
            if ($left > 0 && $targetPeriodId && $targetPeriodId !== (int) $period->id) {
                $target = DB::table('cash_periods')
                    ->where('id', $targetPeriodId)
                    ->where('class_year_id', $yearId)
                    ->first();

                if ($target) {
                    // Hitung sisa kewajiban target dulu (paid + alloc masuk)
                    $paid = (int) DB::table('cash_payments')
                        ->where('class_year_id', $yearId)
                        ->where('enrollment_id', $enrollmentId)
                        ->where('period_id', $target->id)
                        ->sum('amount');

                    $allocated = (int) DB::table('arrear_allocations as a')
                        ->join('cash_payments as p', 'p.id', '=', 'a.payment_id')
                        ->where('p.class_year_id', $yearId)
                        ->where('p.enrollment_id', $enrollmentId)
                        ->where('a.period_id', $target->id)
                        ->sum('a.allocated_amount');

                    $remain = max(0, $nominal - ($paid + $allocated));

                    $take = ($remain > 0) ? min($left, $remain) : 0;
                    if ($take > 0) {
                        $alloc($target->id, $take);
                        $left -= $take;
                    }
                }
            }

            // c) Sisa left (kalau ada) otomatis dianggap murni milik periode OPEN saat ini
            //    (tidak perlu dicatat apa² lagi, karena sudah tercatat di cash_payments di atas)

            // 3) Activity log (best effort)
            try {
                $flags = [];
                if ($arrears->isNotEmpty()) {
                    $flags[] = 'auto-tunggakan';
                }
                if ($targetPeriodId) {
                    $flags[] = 'alokasi-manual#' . $targetPeriodId;
                }

                DB::table('activity_logs')->insert([
                    'class_year_id' => $classYearId,
                    'actor_id'      => auth()->id(),
                    'action'        => 'payment.create',
                    'description'   => 'Tambah pembayaran enrollment #' . $enrollmentId . ' period #' . $period->id . ' Rp ' . number_format($req->amount, 0, ',', '.')
                        . (!empty($flags) ? ' (' . implode(', ', $flags) . ')' : ''),
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            } catch (\Throwable $e) {
                // silent
            }
        });

        return redirect()->route('cash.index')->with('success', 'Pembayaran disimpan.');
    }

    /** Update pembayaran (tanpa ubah alokasi agar simple & aman) */
    public function update(UpdatePaymentRequest $req, CashPayment $pay)
    {
        $periodId    = (int) $req->attributes->get('_period_id');
        $classYearId = (int) $req->attributes->get('_class_year_id');

        $period = DB::table('cash_periods')->where('id', $periodId)->first();
        if (!$period || $period->status !== 'open') {
            return back()->withErrors(['amount' => 'Periode sudah ditutup.']);
        }

        DB::transaction(function () use ($req, $pay, $classYearId) {
            $pay->amount = (int) $req->amount;
            $pay->date   = $req->date;
            $pay->note   = $req->note ?? null;
            $pay->save();

            try {
                DB::table('activity_logs')->insert([
                    'class_year_id' => $classYearId,
                    'actor_id'      => auth()->id(),
                    'action'        => 'payment.update',
                    'description'   => 'Edit pembayaran ID #' . $pay->id,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            } catch (\Throwable $e) {
                // silent
            }
        });

        return back()->with('success', 'Pembayaran diperbarui.');
    }

    /** Hapus pembayaran (hapus alokasinya otomatis karena FK cascade) */
    public function destroy(Request $req, CashPayment $pay)
    {
        $reason = trim((string) $req->input('reason', ''));
        if ($reason === '') {
            return back()->withErrors(['reason' => 'Alasan penghapusan wajib diisi.']);
        }

        $period = DB::table('cash_periods')->where('id', $pay->period_id)->first();
        if (!$period || $period->status !== 'open') {
            return back()->withErrors(['amount' => 'Periode sudah ditutup. Transaksi tidak dapat dihapus.']);
        }

        $snapshot = [
            'class_year_id' => $pay->class_year_id,
            'period_id'     => $pay->period_id,
            'enrollment_id' => $pay->enrollment_id,
            'amount'        => $pay->amount,
            'date'          => $pay->date,
            'note'          => $pay->note,
            'received_by'   => $pay->received_by,
        ];

        DB::transaction(function () use ($pay, $snapshot, $reason) {
            $pay->delete();

            try {
                DB::table('activity_logs')->insert([
                    'class_year_id' => $snapshot['class_year_id'],
                    'actor_id'      => auth()->id(),
                    'action'        => 'payment.delete',
                    'description'   => 'Hapus pembayaran ID #' . $pay->id . ' reason: ' . $reason . ' snapshot: ' . json_encode($snapshot),
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            } catch (\Throwable $e) {
                // silent
            }
        });

        return back()->with('success', 'Pembayaran dihapus.');
    }

    // ===================== Helpers =====================

    /**
     * Hitung map tunggakan untuk semua siswa: [enrollment_id => Collection(periode menunggak)]
     */
    private function computeArrearsMap(int $yearId, int $currentPeriodId, int $nominal): array
    {
        // Ambil semua enrollment di tahun ini
        $enrollIds = DB::table('student_enrollments')
            ->where('class_year_id', $yearId)
            ->where('is_active', 1)
            ->pluck('id')
            ->all();

        $map = [];
        foreach ($enrollIds as $enrollId) {
            $map[$enrollId] = $this->getArrearsForEnrollment($enrollId, $yearId, $currentPeriodId, $nominal);
        }

        return $map;
    }

    /**
     * Daftar periode lama (CLOSED / != current) yang masih sisa, oldest-first, untuk 1 enrollment.
     * sisa = nominal - (sum pembayaran di periode itu + sum alokasi_arrear ke periode itu untuk enrollment tsb)
     */
    private function getArrearsForEnrollment(int $enrollmentId, int $yearId, int $currentPeriodId, int $nominal)
    {
        // Ambil semua periode tahun ini kecuali current, urut paling tua
        $periods = DB::table('cash_periods')
            ->where('class_year_id', $yearId)
            ->where('id', '<>', $currentPeriodId) // exclude current
            ->orderBy('date_start')
            ->get();

        // Total bayar langsung di periode tsb
        $paid = DB::table('cash_payments')
            ->select('period_id', DB::raw('SUM(amount) as total'))
            ->where('class_year_id', $yearId)
            ->where('enrollment_id', $enrollmentId)
            ->groupBy('period_id')
            ->pluck('total', 'period_id'); // [period_id => total]

        // Total alokasi ke periode tsb dari pembayaran minggu lain
        $allocated = DB::table('arrear_allocations as a')
            ->join('cash_payments as p', 'p.id', '=', 'a.payment_id')
            ->select('a.period_id', DB::raw('SUM(a.allocated_amount) as total'))
            ->where('p.class_year_id', $yearId)
            ->where('p.enrollment_id', $enrollmentId)
            ->groupBy('a.period_id')
            ->pluck('total', 'a.period_id'); // [period_id => total]

        $rows = [];
        foreach ($periods as $per) {
            $totalPaid  = (int) ($paid[$per->id] ?? 0);
            $totalAlloc = (int) ($allocated[$per->id] ?? 0);
            $sisa       = max(0, $nominal - ($totalPaid + $totalAlloc));

            if ($sisa > 0) {
                $rows[] = (object) [
                    'period_id'  => $per->id,
                    'week_no'    => $per->week_no,
                    'date_start' => $per->date_start,
                    'date_end'   => $per->date_end,
                    'sisa'       => $sisa,
                ];
            }
        }

        return collect($rows);
    }
}

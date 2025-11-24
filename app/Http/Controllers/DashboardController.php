<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $u = $request->user();

        /*
        |--------------------------------------------------------------------------
        | 1. Ambil class_year_id aktif
        |--------------------------------------------------------------------------
        | Notes:
        | - Semua transaksi cash_* selalu punya class_year_id
        | - Kita pakai tahun ajaran aktif (status = 'active')
        */
        $classYear = DB::table('class_years')
            ->where('status', 'active')
            ->orderBy('id', 'desc')
            ->first([
                'id',
                'class_label',
                'academic_year',
            ]);

        // fallback kalau belum ada data sama sekali
        $classYearId = $classYear->id ?? null;

        /*
        |--------------------------------------------------------------------------
        | 2. Ambil periode kas yang lagi "open" (minggu berjalan) untuk class ini
        |--------------------------------------------------------------------------
        | cash_periods:
        |   - class_year_id
        |   - year
        |   - month
        |   - week_no (minggu ke berapa dalam bulan tsb)
        |   - status ('open' / 'closed')
        */
        $periodeAktif = null;
        if ($classYearId) {
            $periodeAktif = DB::table('cash_periods')
                ->where('class_year_id', $classYearId)
                ->where('status', 'open')
                ->orderBy('date_start', 'desc')
                ->first([
                    'id',
                    'year',
                    'month',
                    'week_no',
                    'date_start',
                    'date_end',
                    'status',
                ]);
        }

        $periodeIdAktif = $periodeAktif->id ?? null;
        $mingguBerjalan = $periodeAktif->week_no ?? null;

        /*
        |--------------------------------------------------------------------------
        | 3. Hitung kas masuk & kas keluar (semua waktu, per tahun ajaran)
        |--------------------------------------------------------------------------
        */
        $totalMasuk  = 0;
        $totalKeluar = 0;

        if ($classYearId) {
            $totalMasuk = DB::table('cash_payments')
                ->where('class_year_id', $classYearId)
                ->sum('amount');

            $totalKeluar = DB::table('cash_expenses')
                ->where('class_year_id', $classYearId)
                ->sum('amount');
        }

        $saldoKas = $totalMasuk - $totalKeluar;

        /*
        |--------------------------------------------------------------------------
        | 4. Pending pengeluaran
        |--------------------------------------------------------------------------
        */
        $pendingPengeluaran = collect();
        if ($classYearId) {
            $pendingPengeluaran = DB::table('expense_requests')
                ->where('class_year_id', $classYearId)
                ->where('status', 'pending')
                ->orderBy('request_date', 'desc')
                ->limit(10)
                ->get([
                    'id',
                    'description as deskripsi',
                    'amount as nominal',
                    'status',
                    'request_date as created_at',
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Laporan cepat bulan ini (buat guru)
        |--------------------------------------------------------------------------
        */
        $now            = Carbon::now();
        $bulanIniMasuk  = 0;
        $bulanIniKeluar = 0;

        if ($classYearId) {
            $bulanIniMasuk = DB::table('cash_payments')
                ->where('class_year_id', $classYearId)
                ->whereMonth('date', $now->month)
                ->whereYear('date', $now->year)
                ->sum('amount');

            $bulanIniKeluar = DB::table('cash_expenses')
                ->where('class_year_id', $classYearId)
                ->whereMonth('date', $now->month)
                ->whereYear('date', $now->year)
                ->sum('amount');
        }

        /*
        |--------------------------------------------------------------------------
        | 6. Ambil nominal kas per minggu dari class_settings
        |--------------------------------------------------------------------------
        */
        $nominalPerMinggu = null;
        if ($classYearId) {
            $nominalPerMinggu = DB::table('class_settings')
                ->where('class_year_id', $classYearId)
                ->value('kas_nominal');
        }

        /*
        |--------------------------------------------------------------------------
        | 7. Siswa belum bayar minggu ini (bendahara dashboard)
        |--------------------------------------------------------------------------
        */
        $siswaBelumBayar = collect();

        if ($classYearId && $periodeIdAktif) {
            // semua siswa aktif di kelas ini
            $semuaSiswaAktif = DB::table('student_enrollments')
                ->join('users', 'student_enrollments.student_user_id', '=', 'users.id')
                ->where('student_enrollments.class_year_id', $classYearId)
                ->where('student_enrollments.is_active', 1)
                ->get([
                    'student_enrollments.id as enrollment_id',
                    'users.name as nama_siswa',
                ]);

            // siapa saja yang SUDAH bayar di periode ini
            $sudahBayarEnrollmentIds = DB::table('cash_payments')
                ->where('class_year_id', $classYearId)
                ->where('period_id', $periodeIdAktif)
                ->pluck('enrollment_id')
                ->unique()
                ->all();

            $siswaBelumBayar = $semuaSiswaAktif
                ->filter(function ($row) use ($sudahBayarEnrollmentIds) {
                    return !in_array($row->enrollment_id, $sudahBayarEnrollmentIds);
                })
                ->map(function ($row) use ($mingguBerjalan, $nominalPerMinggu) {
                    return [
                        'nama'       => $row->nama_siswa,
                        'minggu_ke'  => $mingguBerjalan,
                        'nominal'    => $nominalPerMinggu ?? 0,
                    ];
                })
                ->values();
        }

        /*
        |--------------------------------------------------------------------------
        | 8. Riwayat pembayaran saya (dashboard siswa, 10 terakhir)
        |--------------------------------------------------------------------------
        */
        $riwayatBayarSaya = collect();
        if ($u && $classYearId) {
            $riwayatBayarSaya = DB::table('cash_payments')
                ->join('student_enrollments', 'cash_payments.enrollment_id', '=', 'student_enrollments.id')
                ->join('cash_periods', 'cash_payments.period_id', '=', 'cash_periods.id')
                ->where('student_enrollments.student_user_id', $u->id)
                ->where('cash_payments.class_year_id', $classYearId)
                ->orderBy('cash_payments.date', 'desc')
                ->limit(10)
                ->get([
                    'cash_payments.date as tanggal',
                    'cash_periods.week_no as minggu_ke',
                    'cash_payments.amount as jumlah',
                ])
                ->map(function ($row) {
                    return [
                        'tanggal'    => $row->tanggal,
                        'minggu_ke'  => $row->minggu_ke,
                        'jumlah'     => $row->jumlah,
                        'status'     => 'Terverifikasi',
                    ];
                });
        }

        /*
        |--------------------------------------------------------------------------
        | 9. Ringkasan kewajiban & tunggakan siswa (GLOBAL, bukan per-minggu)
        |--------------------------------------------------------------------------
        | Logika sederhana:
        | - Hitung berapa banyak periode kas yang sudah berjalan (date_start <= hari ini)
        | - Kewajiban total = jumlah_periode * nominalPerMinggu
        | - Total setor siswa = sum(cash_payments.amount) untuk enrollment siswa di tahun aktif
        | - Tunggakan total = max(0, kewajiban total - total setor)
        |
        | Ini view "makro" untuk panel siswa → cukup buat mereka paham:
        | "Sampai minggu ini seharusnya kamu sudah setor sekian, yang sudah masuk sekian, sisa sekian."
        */
        $studentTotalSetorAll   = 0;
        $studentKewajibanTotal  = 0;
        $studentTunggakanTotal  = 0;

        if ($u && $u->hasRole('siswa') && $classYearId && $nominalPerMinggu) {
            // cari enrollment aktif siswa di tahun ini
            $enrollmentIds = DB::table('student_enrollments')
                ->where('class_year_id', $classYearId)
                ->where('student_user_id', $u->id)
                ->where('is_active', 1)
                ->pluck('id');

            if ($enrollmentIds->isNotEmpty()) {
                // total setor semua waktu
                $studentTotalSetorAll = DB::table('cash_payments')
                    ->where('class_year_id', $classYearId)
                    ->whereIn('enrollment_id', $enrollmentIds)
                    ->sum('amount');

                // berapa minggu (periode) yang sudah berjalan sampai hari ini
                $jumlahPeriodeSampaiSekarang = DB::table('cash_periods')
                    ->where('class_year_id', $classYearId)
                    ->whereDate('date_start', '<=', Carbon::now()->toDateString())
                    ->count();

                $studentKewajibanTotal = $jumlahPeriodeSampaiSekarang * $nominalPerMinggu;
                $studentTunggakanTotal = max(0, $studentKewajibanTotal - $studentTotalSetorAll);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 10. Transparansi kelas (untuk siswa)
        |--------------------------------------------------------------------------
        */
        $transparansiKelas = [
            'total_masuk'     => $totalMasuk,
            'total_keluar'    => $totalKeluar,
            'saldo_sisa'      => $saldoKas,
            'minggu_berjalan' => $mingguBerjalan,
        ];

        /*
        |--------------------------------------------------------------------------
        | 11. Data untuk Chart.js (6 bulan terakhir)
        |--------------------------------------------------------------------------
        | Kita buat bar chart:
        | - label: 6 bulan terakhir (mis. Jul 25, Agu 25, dst)
        | - dataset 1: total kas masuk / bulan
        | - dataset 2: total kas keluar / bulan
        */
        $chartLabels = [];
        $chartMasuk  = [];
        $chartKeluar = [];

        if ($classYearId) {
            $start = Carbon::now()->startOfMonth()->subMonths(5); // mundur 5 bulan + bulan berjalan

            for ($i = 0; $i < 6; $i++) {
                $m = (clone $start)->addMonths($i);
                $startDate = $m->toDateString();
                $endDate   = $m->copy()->endOfMonth()->toDateString();

                $label = $m->translatedFormat('M y');
                $chartLabels[] = $label;

                $masuk = DB::table('cash_payments')
                    ->where('class_year_id', $classYearId)
                    ->whereBetween('date', [$startDate, $endDate])
                    ->sum('amount');

                $keluar = DB::table('cash_expenses')
                    ->where('class_year_id', $classYearId)
                    ->whereBetween('date', [$startDate, $endDate])
                    ->sum('amount');

                $chartMasuk[]  = (int) $masuk;
                $chartKeluar[] = (int) $keluar;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 12. Kirim ke Blade
        |--------------------------------------------------------------------------
        */
        return view('dashboard.index', [
            'u'                     => $u,
            'classYear'             => $classYear,
            'period'                => $periodeAktif,
            'nominal'               => $nominalPerMinggu,

            'saldoKas'              => $saldoKas,
            'pendingPengeluaran'    => $pendingPengeluaran,
            'siswaBelumBayar'       => $siswaBelumBayar,
            'riwayatBayarSaya'      => $riwayatBayarSaya,
            'transparansiKelas'     => $transparansiKelas,
            'bulanIniMasuk'         => $bulanIniMasuk,
            'bulanIniKeluar'        => $bulanIniKeluar,

            // ringkasan tunggakan siswa
            'studentTotalSetorAll'  => $studentTotalSetorAll,
            'studentKewajibanTotal' => $studentKewajibanTotal,
            'studentTunggakanTotal' => $studentTunggakanTotal,

            // data grafik
            'chartLabels'           => $chartLabels,
            'chartMasuk'            => $chartMasuk,
            'chartKeluar'           => $chartKeluar,
        ]);
    }
}

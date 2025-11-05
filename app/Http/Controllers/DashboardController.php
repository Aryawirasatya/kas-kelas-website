<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index (Request $request)
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
                ]);
        }

        $periodeIdAktif = $periodeAktif->id ?? null;
        $mingguBerjalan = $periodeAktif->week_no ?? null;

        /*
        |--------------------------------------------------------------------------
        | 3. Hitung kas masuk & kas keluar
        |--------------------------------------------------------------------------
        | kas masuk  = sum(cash_payments.amount)
        | kas keluar = sum(cash_expenses.amount)
        | semua dibatasi per class_year_id
        |
        | NOTE: cash_payments TIDAK punya kolom status → berarti semua tercatat dianggap sah.
        | cash_expenses adalah pengeluaran yang SUDAH APPROVED.
        */
        $totalMasuk = 0;
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
        | expense_requests:
        |   - status = 'pending'
        |   - class_year_id = kelas aktif
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
        | Kita pakai created_at transaksi bulan ini, per class_year_id.
        | Kalau kamu mau pakai kolom date/request_date instead of created_at,
        | gampang diganti nanti.
        */
        $now = Carbon::now();
        $bulanIniMasuk = 0;
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
        | class_settings:
        |   - class_year_id
        |   - kas_nominal
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
        | Cara pikir:
        | - ambil semua siswa aktif di student_enrollments untuk class_year_id aktif
        | - untuk masing-masing siswa, cek apakah ADA baris cash_payments
        |   di periodeAktif (period_id = periodeIdAktif)
        | - kalau TIDAK ADA -> dia masuk list belum bayar
        |
        | Column penting:
        |   student_enrollments.id            = enrollment_id
        |   student_enrollments.student_user_id (link ke users.id)
        |   cash_payments.enrollment_id
        |   cash_payments.period_id
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

            // buat map siapa aja yang SUDAH bayar di periode ini
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
        | 8. Riwayat pembayaran saya (dashboard siswa)
        |--------------------------------------------------------------------------
        | Kita ambil cash_payments milik siswa login.
        | Hubungannya:
        |   - user siswa -> student_enrollments (by student_user_id)
        |   - cash_payments.enrollment_id -> student_enrollments.id
        |   - cash_payments.period_id -> cash_periods.id
        |
        | Kita hanya tampilkan transaksi kelas aktif ini.
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
                        'status'     => 'Terverifikasi', // tidak ada kolom status di cash_payments, kita tandai verified
                    ];
                });
        }

        /*
        |--------------------------------------------------------------------------
        | 9. Transparansi kelas (untuk siswa)
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
        | 10. Kirim ke Blade
        |--------------------------------------------------------------------------
        */
        return view('dashboard.index', [
            'u'                   => $u,
            'saldoKas'            => $saldoKas,
            'pendingPengeluaran'  => $pendingPengeluaran,
            'siswaBelumBayar'     => $siswaBelumBayar,
            'riwayatBayarSaya'    => $riwayatBayarSaya,
            'transparansiKelas'   => $transparansiKelas,
            'bulanIniMasuk'       => $bulanIniMasuk,
            'bulanIniKeluar'      => $bulanIniKeluar,
        ]);
    }
}

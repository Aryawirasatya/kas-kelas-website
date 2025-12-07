<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\YearController;
use App\Http\Controllers\PeriodController;
use App\Http\Controllers\CashController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\UnpaidReasonController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ExpenseRequestController;
use App\Http\Controllers\CashExpenseController;

// ======================================================================
// Redirect root → dashboard
// ======================================================================
Route::get('/', fn () => redirect()->route('dashboard'));

// ======================================================================
// Dashboard & Profile (user login)
// ======================================================================
Route::middleware('auth')->group(function () {
    // Dashboard (butuh verified)
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('verified')
        ->name('dashboard');

    // Profil akun
    Route::get('/profile',  [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ======================================================================
// Tahun Ajaran (Guru saja)
// ======================================================================
Route::middleware(['auth', 'role:guru'])->group(function () {
    Route::get ('/tahun-ajaran',                    [YearController::class, 'index'])->name('year.index');
    Route::post('/tahun-ajaran',                    [YearController::class, 'storeDraft'])->name('year.store');

    // Setting kas
    Route::get ('/tahun-ajaran/{year}/setting',     [YearController::class, 'editSetting'])->name('year.setting');
    Route::put ('/tahun-ajaran/{year}/setting',     [YearController::class, 'updateSetting']);

    // Siswa & bendahara
    Route::get ('/tahun-ajaran/{year}/students',               [YearController::class, 'students'])->name('year.students');
    Route::post('/tahun-ajaran/{year}/students/import',        [YearController::class, 'importStudents'])->name('year.students.import');
    Route::post('/tahun-ajaran/{year}/students/manual',        [YearController::class, 'storeStudentManual'])->name('year.students.manual');
    Route::post('/tahun-ajaran/{year}/treasurers',             [YearController::class, 'assignTreasurers'])->name('year.treasurers');

    Route::post  ('/tahun-ajaran/{year}/students/bulk',        [YearController::class, 'bulkStudents'])->name('year.students.bulk');
    Route::patch ('/tahun-ajaran/{year}/enrollments/{enrollment}',        [YearController::class, 'updateEnrollment'])->name('year.enrollment.update');
    Route::post  ('/tahun-ajaran/{year}/enrollments/{enrollment}/toggle', [YearController::class, 'toggleEnrollment'])->name('year.enrollment.toggle');
    Route::delete('/tahun-ajaran/{year}/enrollments/{enrollment}',        [YearController::class, 'destroyEnrollment'])->name('year.enrollment.destroy');

    // Aktivasi & tutup tahun
    Route::post('/tahun-ajaran/{year}/activate',    [YearController::class, 'activate'])->name('year.activate');
    Route::post('/tahun-ajaran/{year}/close',       [YearController::class, 'close'])->name('year.close');

    // Ringkasan satu tahun ajaran
    Route::get ('/tahun-ajaran/{year}/ringkasan',   [YearController::class, 'summary'])->name('year.summary');
});

// ======================================================================
// Periode Kas (index untuk semua login, manage untuk guru & bendahara)
// ======================================================================
Route::middleware('auth')->group(function () {
    // Semua user login bisa lihat daftar periode (untuk transparansi)
    Route::get('/periode', [PeriodController::class, 'index'])->name('period.index');

    // Bikin / buka / tutup periode: guru & bendahara
    Route::middleware(['role:guru|bendahara'])->group(function () {
        // Buat minggu berjalan (start=today, end=today+4) & langsung OPEN
        Route::post('/periode/open-today', [PeriodController::class, 'createToday'])->name('period.openToday');

        // Alias "generate" → tetap pakai createToday()
        Route::post('/periode/generate',   [PeriodController::class, 'createToday'])->name('period.generate');

        // Buka / tutup periode tertentu
        Route::post('/periode/{period}/open',  [PeriodController::class, 'open'])->name('period.open');
        Route::post('/periode/{period}/close', [PeriodController::class, 'close'])->name('period.close');
    });
});

// ======================================================================
// Kas Masuk (Bendahara)
// ======================================================================
Route::middleware(['auth', 'role:bendahara'])->group(function () {
    Route::get   ('/kas',                [CashController::class, 'index'])->name('cash.index');
    Route::post  ('/kas/payments',       [CashController::class, 'store'])->name('cash.store');
    Route::put   ('/kas/payments/{pay}', [CashController::class, 'update'])->name('cash.update');
    Route::delete('/kas/payments/{pay}', [CashController::class, 'destroy'])->name('cash.destroy');

    // Alasan belum bayar / belum lunas
    Route::post('/kas/unpaid-reason', [UnpaidReasonController::class, 'store'])->name('cash.unpaidReason.store');
    Route::put ('/kas/unpaid-reason', [UnpaidReasonController::class, 'update'])->name('cash.unpaidReason.update');
});

// ======================================================================
// Kategori, Pengajuan Pengeluaran, Export Laporan & Activity Log
// (Guru & Bendahara saja)
// ======================================================================
Route::middleware(['auth', 'role:guru|bendahara'])->group(function () {
    // Kategori (global, tidak per-tahun)
    Route::resource('categories', CategoryController::class);

    // Pengajuan pengeluaran (bendahara buat, guru review)
    Route::resource('expense-requests', ExpenseRequestController::class)
        ->only(['index', 'create', 'store', 'show', 'destroy']);

    // Export laporan umum (hanya guru & bendahara)
    Route::get('/reports/export/excel',  [ReportController::class, 'exportExcel'])->name('reports.export.excel');
    Route::get('/reports/export/pdf',    [ReportController::class, 'exportPdf'])->name('reports.export.pdf');

    // Activity log (audit trail) + export → hanya guru & bendahara
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    Route::get('/activity-logs/export/excel', [ActivityLogController::class, 'exportExcel'])
        ->name('activity-logs.export-excel');
    Route::get('/activity-logs/export/pdf', [ActivityLogController::class, 'exportPdf'])
        ->name('activity-logs.export-pdf');
});

// ======================================================================
// Halaman Laporan Utama (Guru, Bendahara, Siswa)
// ======================================================================
Route::middleware(['auth', 'role:guru|bendahara|siswa'])->group(function () {
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
});

// ======================================================================
// ACC / Reject Pengeluaran (hanya Guru)
// ======================================================================
Route::middleware(['auth', 'role:guru'])->group(function () {
    Route::post('/expense-requests/{expenseRequest}/approve', [CashExpenseController::class, 'approveFromRequest'])
        ->name('expense-requests.approve');

    Route::post('/expense-requests/{expenseRequest}/reject', [ExpenseRequestController::class, 'reject'])
        ->name('expense-requests.reject');
});

// ======================================================================
// Laporan Personal Siswa
// ======================================================================
Route::middleware(['auth', 'role:siswa'])->group(function () {
    Route::get('/reports/export/personal/excel', [ReportController::class, 'exportPersonalExcel'])
        ->name('reports.export.personal.excel');

    Route::get('/reports/export/personal/pdf', [ReportController::class, 'exportPersonalPdf'])
        ->name('reports.export.personal.pdf');
});

// ======================================================================
// Auth routes (Breeze / Fortify / dll.)
// ======================================================================
require __DIR__ . '/auth.php';

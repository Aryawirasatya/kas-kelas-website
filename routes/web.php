<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\YearController;
use App\Http\Controllers\PeriodController;
use App\Http\Controllers\CashController;
use App\Http\Controllers\UnpaidReasonController;

Route::get('/', fn () => redirect()->route('dashboard'));

/**
 * Dashboard
 */
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

/**
 * Profile
 */
Route::middleware('auth')->group(function () {
    Route::get('/profile',  [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/**
 * Tahun Ajaran (Guru)
 */
Route::middleware(['auth', 'role:guru'])->group(function () {
    Route::get ('/tahun-ajaran',                               [YearController::class, 'index'])->name('year.index');
    Route::post('/tahun-ajaran',                               [YearController::class, 'storeDraft'])->name('year.store');

    Route::get ('/tahun-ajaran/{year}/setting',                [YearController::class, 'editSetting'])->name('year.setting');
    Route::put ('/tahun-ajaran/{year}/setting',                [YearController::class, 'updateSetting']);

    Route::get ('/tahun-ajaran/{year}/students',               [YearController::class, 'students'])->name('year.students');
    Route::post('/tahun-ajaran/{year}/students/import',        [YearController::class, 'importStudents'])->name('year.students.import');
    Route::post('/tahun-ajaran/{year}/students/manual',        [YearController::class, 'storeStudentManual'])->name('year.students.manual');
    Route::post('/tahun-ajaran/{year}/treasurers',             [YearController::class, 'assignTreasurers'])->name('year.treasurers');

    Route::post('/tahun-ajaran/{year}/students/bulk',          [YearController::class, 'bulkStudents'])->name('year.students.bulk');

    Route::patch ('/tahun-ajaran/{year}/enrollments/{enrollment}',        [YearController::class, 'updateEnrollment'])->name('year.enrollment.update');
    Route::post  ('/tahun-ajaran/{year}/enrollments/{enrollment}/toggle', [YearController::class, 'toggleEnrollment'])->name('year.enrollment.toggle');
    Route::delete('/tahun-ajaran/{year}/enrollments/{enrollment}',        [YearController::class, 'destroyEnrollment'])->name('year.enrollment.destroy');

    Route::post('/tahun-ajaran/{year}/periods/generate',       [YearController::class, 'generatePeriods'])->name('year.periods.generate');
    Route::post('/tahun-ajaran/{year}/activate',               [YearController::class, 'activate'])->name('year.activate');
    Route::post('/tahun-ajaran/{year}/close',                  [YearController::class, 'close'])->name('year.close');

    // ⬇⬇⬇ BARU: halaman ringkasan 1 tahun ajaran (active / archived) ⬇⬇⬇
    Route::get('/tahun-ajaran/{year}/ringkasan',               [YearController::class, 'summary'])->name('year.summary');
});

/**
 * Periode (index untuk semua user login; buat/open/close untuk guru & bendahara)
 * Catatan: method controller yang tersedia adalah createToday(), open(), close().
 *          Route "generate" kita arahkan ke createToday() agar sesuai implementasi.
 */
Route::middleware(['auth'])->group(function () {
    Route::get('/periode', [PeriodController::class, 'index'])->name('period.index');

    Route::middleware(['role:guru|bendahara'])->group(function () {
        // Buat minggu berjalan (start = today, end = today+4) dan langsung OPEN
        Route::post('/periode/open-today', [PeriodController::class, 'createToday'])->name('period.openToday');

        // Alias "generate" → sama-sama memakai createToday() sesuai konsep periode manual satu-per-satu
        Route::post('/periode/generate',    [PeriodController::class, 'createToday'])->name('period.generate');

        // Buka/Tutup periode tertentu
        Route::post('/periode/{period}/open',  [PeriodController::class, 'open'])->name('period.open');
        Route::post('/periode/{period}/close', [PeriodController::class, 'close'])->name('period.close');
    });
});

/**
 * Kas (Bendahara)
 */
Route::middleware(['auth', 'role:bendahara'])->group(function () {
    Route::get   ('/kas',                   [CashController::class, 'index'])->name('cash.index');
    Route::post  ('/kas/payments',          [CashController::class, 'store'])->name('cash.store');
    Route::put   ('/kas/payments/{pay}',    [CashController::class, 'update'])->name('cash.update');
    Route::delete('/kas/payments/{pay}',    [CashController::class, 'destroy'])->name('cash.destroy');

    // Alasan Belum Lunas
    Route::post('/kas/unpaid-reason',       [UnpaidReasonController::class, 'store'])->name('cash.unpaidReason.store');
    // (opsional jika dipakai): update alasan via PUT
    Route::put ('/kas/unpaid-reason',       [UnpaidReasonController::class, 'update'])->name('cash.unpaidReason.update');
});

require __DIR__ . '/auth.php';

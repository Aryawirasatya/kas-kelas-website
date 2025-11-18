<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\YearController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ExpenseRequestController;
use App\Http\Controllers\CashExpenseController;
use Illuminate\Support\Facades\Route;


Route::get('/', fn() => redirect()->route('dashboard'));

Route::get('/dashboard', [DashboardController::class,'index'])
  ->middleware(['auth','verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
  Route::get('/profile',  [ProfileController::class,'edit'])->name('profile.edit');
  Route::patch('/profile', [ProfileController::class,'update'])->name('profile.update');
  Route::delete('/profile',[ProfileController::class,'destroy'])->name('profile.destroy');
});

Route::middleware(['auth','role:guru'])->group(function () {
  // Index + Buat draft
  Route::get ('/tahun-ajaran',           [YearController::class,'index'])->name('year.index');
  Route::post('/tahun-ajaran',           [YearController::class,'storeDraft'])->name('year.store');

  // Step 2: setting nominal
  Route::get ('/tahun-ajaran/{year}/setting', [YearController::class,'editSetting'])->name('year.setting');
  Route::put ('/tahun-ajaran/{year}/setting', [YearController::class,'updateSetting']);

  // Step 3 & 4: siswa + bendahara
  Route::get ('/tahun-ajaran/{year}/students',        [YearController::class,'students'])->name('year.students');
  Route::post('/tahun-ajaran/{year}/students/import', [YearController::class,'importStudents'])->name('year.students.import');
  Route::post('/tahun-ajaran/{year}/students/manual', [YearController::class,'storeStudentManual'])->name('year.students.manual');
  Route::post('/tahun-ajaran/{year}/treasurers',      [YearController::class,'assignTreasurers'])->name('year.treasurers');

  // BULK siswa (draft only)
  Route::post('/tahun-ajaran/{year}/students/bulk',   [YearController::class,'bulkStudents'])->name('year.students.bulk');

  // CRUD enrollment satuan
  Route::patch ('/tahun-ajaran/{year}/enrollments/{enrollment}',         [YearController::class,'updateEnrollment'])->name('year.enrollment.update');
  Route::post  ('/tahun-ajaran/{year}/enrollments/{enrollment}/toggle',  [YearController::class,'toggleEnrollment'])->name('year.enrollment.toggle');
  Route::delete('/tahun-ajaran/{year}/enrollments/{enrollment}',         [YearController::class,'destroyEnrollment'])->name('year.enrollment.destroy');

  // Periode + Aktivasi/Tutup
  Route::post('/tahun-ajaran/{year}/periods/generate', [YearController::class,'generatePeriods'])->name('year.periods.generate');
  Route::post('/tahun-ajaran/{year}/activate',         [YearController::class,'activate'])->name('year.activate');
  Route::post('/tahun-ajaran/{year}/close',            [YearController::class,'close'])->name('year.close');
});

  // web CRUD (view + controller)
  Route::middleware(['auth','role:bendahara'])->group(function () {
    Route::resource('categories', CategoryController::class);
    Route::resource('expense_requests', ExpenseRequestController::class);

});


require __DIR__.'/auth.php';

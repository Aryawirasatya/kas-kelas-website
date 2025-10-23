<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // redirect ke route bernama 'dashboard' (bukan 'dashboard.index')
    return redirect()->route('dashboard');
});

Route::get('/dashboard', function () {
    // render view custom kamu: resources/views/dashboard/index.blade.php
    return view('dashboard.index');
})->middleware(['auth', 'verified'])->name('dashboard'); // <- namanya 'dashboard' saja

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

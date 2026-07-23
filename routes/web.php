<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Admin\AdminController;

Route::redirect('/', '/absensi');

// Absensi (public)
Route::get('/absensi', [AttendanceController::class, 'index'])->name('attendance.index');
Route::post('/absensi/search', [AttendanceController::class, 'search'])->name('attendance.search');
Route::post('/absensi/checkin', [AttendanceController::class, 'checkIn'])->name('attendance.checkin');
Route::get('/absensi/kupon/{nomor_induk}', [AttendanceController::class, 'kupon'])->name('attendance.kupon');

// Admin SSO Login
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminController::class, 'login'])->name('login');
    Route::get('/redirect', [AdminController::class, 'redirect'])->name('redirect');
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::post('/pin', [AdminController::class, 'updatePin'])->name('pin.update');
    Route::match(['get', 'post'], '/logout', [AdminController::class, 'logout'])->name('logout');
});

// SSO Callback (harus sesuai dengan redirect URI di SSO server)
Route::get('/callback', [AdminController::class, 'callback'])->name('admin.callback');

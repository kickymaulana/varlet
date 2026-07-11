<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\AttendanceController;

Route::redirect('/', '/absensi');

Route::get('/absensi', [AttendanceController::class, 'index'])->name('attendance.index');
Route::post('/absensi/search', [AttendanceController::class, 'search'])->name('attendance.search');
Route::post('/absensi/checkin', [AttendanceController::class, 'checkIn'])->name('attendance.checkin');

<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\LuckyDrawController;
use App\Http\Controllers\Admin\ParticipantController;

Route::redirect('/', '/absensi');

// Absensi (public)
Route::get('/absensi', [AttendanceController::class, 'index'])->name('attendance.index');
Route::post('/absensi/search', [AttendanceController::class, 'search'])->name('attendance.search');
Route::post('/absensi/checkin', [AttendanceController::class, 'checkIn'])->name('attendance.checkin');
Route::get('/absensi/kupon/{nomor_induk}', [AttendanceController::class, 'kupon'])->name('attendance.kupon');

// Admin
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminController::class, 'login'])->name('login');
    Route::get('/redirect', [AdminController::class, 'redirect'])->name('redirect');
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::post('/pin', [AdminController::class, 'updatePin'])->name('pin.update');
    Route::match(['get', 'post'], '/logout', [AdminController::class, 'logout'])->name('logout');

    // Lucky Draw
    Route::get('/lucky-draw/prizes', [LuckyDrawController::class, 'prizes'])->name('lucky-draw.prizes');
    Route::post('/lucky-draw/prizes', [LuckyDrawController::class, 'storePrize'])->name('lucky-draw.prizes.store');
    Route::put('/lucky-draw/prizes/{id}', [LuckyDrawController::class, 'updatePrize'])->name('lucky-draw.prizes.update');
    Route::delete('/lucky-draw/prizes/{id}', [LuckyDrawController::class, 'deletePrize'])->name('lucky-draw.prizes.delete');
    Route::get('/lucky-draw/draw', [LuckyDrawController::class, 'drawPage'])->name('lucky-draw.draw');
    Route::post('/lucky-draw/draw/start', [LuckyDrawController::class, 'startDraw'])->name('lucky-draw.draw.start');
    Route::post('/lucky-draw/reset', [LuckyDrawController::class, 'resetDraw'])->name('lucky-draw.reset');

    // Participants CRUD
    Route::get('/participants', [ParticipantController::class, 'index'])->name('participants.index');
    Route::post('/participants', [ParticipantController::class, 'store'])->name('participants.store');
    Route::put('/participants/{id}', [ParticipantController::class, 'update'])->name('participants.update');
    Route::delete('/participants/{id}', [ParticipantController::class, 'destroy'])->name('participants.destroy');
});

// Lucky Draw Display (public — for projector/TV)
Route::get('/undian/show', [LuckyDrawController::class, 'display'])->name('lucky-draw.display');
Route::get('/undian/data', [LuckyDrawController::class, 'displayData'])->name('lucky-draw.display-data');
Route::any('/undian/draw', [LuckyDrawController::class, 'executeDraw'])->name('lucky-draw.display-draw');

// SSO Callback
Route::get('/callback', [AdminController::class, 'callback'])->name('admin.callback');

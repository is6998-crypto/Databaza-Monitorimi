<?php

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Monitor\MonitorAppController;
use App\Http\Controllers\SessionController;
use App\Http\Middleware\EnsureStaffWithMfa;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Staff / shared
|--------------------------------------------------------------------------
*/

Route::get('/locale/{locale}', LocaleController::class)->name('locale.switch');

Route::middleware(['auth', SetLocale::class])->group(function (): void {
    Route::post('/session/ping', [SessionController::class, 'ping'])->name('session.ping');
    Route::get('/attachments/{attachment}/download', [AttachmentController::class, 'download'])
        ->middleware('signed')
        ->name('attachments.download');
});

Route::get('/', fn () => redirect('/admin'));

/*
|--------------------------------------------------------------------------
| Monitor app (installable PWA, offline drafts) — /m
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', SetLocale::class, EnsureStaffWithMfa::class])->prefix('m')->name('monitor.')->group(function (): void {
    Route::get('/', [MonitorAppController::class, 'index'])->name('app');
    Route::get('/api/bootstrap', [MonitorAppController::class, 'bootstrap'])->name('bootstrap');
    Route::post('/api/hearings', [MonitorAppController::class, 'storeHearing'])->middleware('throttle:monitor-sync')->name('hearings.store');
});

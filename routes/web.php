<?php

use App\Http\Controllers\LocaleController;
use App\Http\Controllers\SessionController;
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
});

Route::get('/', fn () => redirect('/admin'));

Route::middleware(['auth', SetLocale::class])->group(function (): void {
    Route::get('/m', fn () => redirect('/admin'))->name('monitor.app');
});

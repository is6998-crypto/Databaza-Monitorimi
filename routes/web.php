<?php

use App\Http\Controllers\ApiDocsController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Monitor\MonitorAppController;
use App\Http\Controllers\Portal\PortalController;
use App\Http\Controllers\SessionController;
use App\Http\Middleware\EnsureStaffWithMfa;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetPortalLocale;
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

/*
|--------------------------------------------------------------------------
| Researcher API documentation (Scribe output, regenerate with `composer docs:api`)
|--------------------------------------------------------------------------
*/

Route::get('/docs/api', [ApiDocsController::class, 'show'])->name('scribe');
Route::get('/docs/api.openapi', [ApiDocsController::class, 'openapi'])->name('scribe.openapi');
Route::get('/docs/api.postman', [ApiDocsController::class, 'postman'])->name('scribe.postman');

/*
|--------------------------------------------------------------------------
| Public portal — /sq/... and /en/... (proposal B6.7)
|--------------------------------------------------------------------------
*/

Route::get('/', [PortalController::class, 'redirect'])->name('portal.root');
Route::get('/sitemap.xml', [PortalController::class, 'sitemap'])->name('portal.sitemap');

Route::prefix('{locale}')
    ->where(['locale' => 'sq|en'])
    ->middleware(SetPortalLocale::class)
    ->name('portal.')
    ->group(function (): void {
        Route::get('/', [PortalController::class, 'home'])->name('home');
        Route::get('/cases', [PortalController::class, 'cases'])->name('cases');
        Route::get('/cases/{publicRef}', [PortalController::class, 'case'])->name('cases.show');
        Route::get('/reports', [PortalController::class, 'publications'])->name('publications');
        Route::get('/reports/{slug}', [PortalController::class, 'publication'])->name('publications.show');
        Route::get('/reports/{slug}/download', [PortalController::class, 'download'])->name('publications.download');
        Route::get('/open-data', [PortalController::class, 'openData'])->name('open-data');
        Route::get('/open-data/{dataset}.csv', [PortalController::class, 'dataset'])->middleware('throttle:portal-search')->name('open-data.download');

        foreach (PortalController::PAGES as $page) {
            Route::get('/'.$page, [PortalController::class, 'page'])->defaults('page', $page)->name($page);
        }
    });

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

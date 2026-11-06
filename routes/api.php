<?php

use App\Http\Controllers\Api\V1\CaseController;
use App\Http\Controllers\Api\V1\HearingController;
use App\Http\Controllers\Api\V1\PartyController;
use App\Http\Controllers\Api\V1\ReferenceController;
use App\Http\Controllers\Api\V1\StatisticsController;
use App\Http\Middleware\EnsureApprovedResearcher;
use App\Http\Middleware\SetApiLocale;
use Illuminate\Support\Facades\Route;

/*
| Researcher API (/api/v1): read-only, Sanctum tokens with the "research:read" ability,
| approved researchers only, rate limited, every record-level response logged.
| OpenAPI documentation: /docs/api
*/

Route::prefix('v1')
    ->name('api.v1.')
    ->middleware([SetApiLocale::class, 'auth:sanctum', EnsureApprovedResearcher::class, 'throttle:api'])
    ->group(function (): void {
        Route::get('/cases', [CaseController::class, 'index'])->name('cases.index');
        Route::get('/cases/{ref}', [CaseController::class, 'show'])->name('cases.show');
        Route::get('/hearings', [HearingController::class, 'index'])->name('hearings.index');
        Route::get('/parties', [PartyController::class, 'index'])->name('parties.index');
        Route::get('/statistics', [StatisticsController::class, 'index'])->name('statistics');
        Route::get('/reference/courts', [ReferenceController::class, 'courts'])->name('reference.courts');
        Route::get('/reference/offences', [ReferenceController::class, 'offences'])->name('reference.offences');
        Route::get('/reference/codes', [ReferenceController::class, 'codes'])->name('reference.codes');
    });

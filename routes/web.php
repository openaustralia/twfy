<?php

/**
 * @file
 * Laravel's web routes. Apache sends each address here through www/docs/laravel.php. Fortify adds the sign-in routes.
 */

use Illuminate\Support\Facades\Route;
use OpenAustralia\TWFY\Http\Controllers\MemberLookupController;
use OpenAustralia\TWFY\Http\Controllers\RepresentativeController;
use OpenAustralia\TWFY\Http\Controllers\SenatorController;

// Public pages skip the web group, so reading one starts no session. init.php already knows who is signed in.
Route::withoutMiddleware('web')->group(function (): void {
    Route::get('/mps', [RepresentativeController::class, 'index'])->name('representatives.index');
    Route::get('/senators', [SenatorController::class, 'index'])->name('senators.index');
    Route::get('/mp', MemberLookupController::class)->name('representatives.lookup');
    Route::get('/senator', MemberLookupController::class)->name('senators.lookup');
    Route::get('/mp/{name}/{electorate?}', [RepresentativeController::class, 'show'])->name('representatives.show');
    Route::get('/senator/{name}/{state?}', [SenatorController::class, 'show'])->name('senators.show');
});

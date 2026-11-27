<?php

/**
 * @file
 * Laravel's web routes. Apache sends each address here through www/docs/laravel.php. Fortify adds the sign-in routes.
 */

use Illuminate\Support\Facades\Route;
use OpenAustralia\TWFY\Http\Controllers\AccountAlertController;
use OpenAustralia\TWFY\Http\Controllers\AccountController;
use OpenAustralia\TWFY\Http\Controllers\EmailChangeController;
use OpenAustralia\TWFY\Http\Controllers\MemberLookupController;
use OpenAustralia\TWFY\Http\Controllers\RepresentativeController;
use OpenAustralia\TWFY\Http\Controllers\SenatorController;

// Public pages skip the web group, so reading one starts no session. init.php already knows who is signed in.
Route::withoutMiddleware('web')->group(function (): void {
    Route::get('/mps', [RepresentativeController::class, 'index'])->name('representatives.index');
    Route::get('/senators', [SenatorController::class, 'index'])->name('senators.index');
    Route::get('/mp', MemberLookupController::class)->name('representatives.lookup');
    Route::get('/senator', MemberLookupController::class)->name('senators.lookup');
    // Old bookmarks name the file that once answered. These come first, so the file isn't read as someone's name.
    $oldFiles = 'index\.php|mobile\.php';
    Route::get('/mp/{file}', MemberLookupController::class)->where('file', $oldFiles)->name('representatives.file');
    Route::get('/senator/{file}', MemberLookupController::class)->where('file', $oldFiles)->name('senators.file');
    Route::get('/mp/{name}/{electorate?}', [RepresentativeController::class, 'show'])->name('representatives.show');
    Route::get('/senator/{name}/{state?}', [SenatorController::class, 'show'])->name('senators.show');
});

// Someone's own details and alerts. Only they can see them, and nobody's details are public.
Route::middleware('auth')->prefix('account')->name('account.')->group(function (): void {
    Route::get('/', [AccountController::class, 'show'])->name('show');
    Route::get('/edit', [AccountController::class, 'edit'])->name('edit');
    Route::put('/', [AccountController::class, 'update'])->name('update');
    // The link in the email to the new address, which signed-out people sign in to follow.
    Route::get('/email/confirm/{user}/{hash}', [EmailChangeController::class, 'confirm'])
      ->middleware(['signed', 'throttle:6,1'])
      ->whereNumber('user')
      ->name('email.confirm');
    Route::post('/email/resend', [EmailChangeController::class, 'resend'])->name('email.resend');
    Route::delete('/email', [EmailChangeController::class, 'cancel'])->name('email.cancel');
    Route::delete('/alerts/{alert}', [AccountAlertController::class, 'destroy'])->whereNumber('alert')->name('alerts.destroy');
    Route::put('/alerts/{alert}', [AccountAlertController::class, 'restore'])->whereNumber('alert')->name('alerts.restore');
});

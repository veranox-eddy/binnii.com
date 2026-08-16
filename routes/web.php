<?php

use App\Http\Controllers\SignupController;
use Illuminate\Support\Facades\Route;

// Everything except /signup/* is served as static files by nginx from
// public/ (Home/Features/Pricing/FAQ/About + brand assets).
Route::redirect('/', '/Home.dc.html');

Route::middleware('throttle:10,1')->group(function () {
    Route::get('/signup', [SignupController::class, 'account'])->name('signup.account');
    Route::post('/signup', [SignupController::class, 'storeAccount'])->name('signup.account.store');
    Route::get('/signup/organization', [SignupController::class, 'organization'])->name('signup.organization');
    Route::post('/signup/organization', [SignupController::class, 'store'])->name('signup.organization.store');
    Route::get('/signup/check-email', [SignupController::class, 'checkEmail'])->name('signup.check-email');
    Route::post('/signup/resend', [SignupController::class, 'resend'])->name('signup.resend');
});

Route::get('/signup/verify/{token}', [SignupController::class, 'verify'])
    ->middleware('throttle:10,1')->name('signup.verify');

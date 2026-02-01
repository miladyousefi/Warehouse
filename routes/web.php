<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
        'demoLogin' => [
            'email' => env('DEMO_LOGIN_EMAIL', 'admin@thehunger.com'),
            'password' => env('DEMO_LOGIN_PASSWORD', 'password'),
        ],
    ]);
})->name('home');

Route::get('dashboard', [\App\Http\Controllers\Warehouse\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Locale switch route: sets cookie and session, then redirects back
Route::get('locale/{locale}', function (string $locale) {
    $available = ['en', 'tr'];
    if (! in_array($locale, $available, true)) {
        $locale = config('app.locale');
    }

    // Store in session for authenticated users
    if (auth()->check()) {
        session(['locale' => $locale]);
    }

    // Also set cookie for persistent storage (1 year)
    $response = redirect()->back();
    return $response->withCookie(
        cookie('locale', $locale, 525600, '/', null, false, false)
    );
})->name('locale.switch');

require __DIR__.'/warehouse.php';
require __DIR__.'/settings.php';

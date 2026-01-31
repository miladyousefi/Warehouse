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

require __DIR__.'/warehouse.php';
require __DIR__.'/settings.php';

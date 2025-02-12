<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    // ...existing dashboard routes...

    // Helpdesk Routes
    Route::get('/helpdesk/support', function () {
        return Inertia::render('Helpdesk/Support');
    })->name('helpdesk.support');

    Route::get('/helpdesk/settings', function () {
        return Inertia::render('Helpdesk/Settings');
    })->name('helpdesk.settings');

    Route::get('/helpdesk/users', function () {
        return Inertia::render('Helpdesk/Users');
    })->name('helpdesk.users');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

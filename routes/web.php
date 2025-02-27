<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\RepairController;
use App\Http\Controllers\DeliveryNoteController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [UserController::class, 'show'])->name('dashboard.show');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

//pages routes
Route::get('/clients', [ClientController::class, 'index'])->name('clients');
Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles');
Route::get('/repairs', [RepairController::class, 'index'])->name('repairs');
Route::get('/delivery-notes', [DeliveryNoteController::class, 'index'])->name('delivery_notes.show');

require __DIR__.'/auth.php';

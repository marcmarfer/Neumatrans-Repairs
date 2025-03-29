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
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\FamilyController;
use App\Http\Controllers\InsightsController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [UserController::class, 'show'])->name('dashboard.show');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

//pages routes
Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');

Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
Route::post('/vehicles', [VehicleController::class, 'store'])->name('vehicles.store');

Route::get('/repairs', [RepairController::class, 'index'])->name('repairs.index');
Route::post('/repairs', [RepairController::class, 'store'])->name('repairs.store');

Route::get('/delivery-notes', [DeliveryNoteController::class, 'index'])->name('delivery_notes.index');
Route::post('/delivery-notes', [DeliveryNoteController::class, 'store'])->name('delivery_notes.store');

Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
Route::post('/families', [FamilyController::class, 'store'])->name('families.store');

Route::get('/insights', [InsightsController::class, 'index'])->name('insights.index');
Route::post('/insights/query', [InsightsController::class, 'getQueryResults'])->name('insights.query');

require __DIR__.'/auth.php';

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
use App\Http\Middleware\ProductionMiddleware;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [UserController::class, 'show'])->name('dashboard.show');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

//pages routes
Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
Route::put('/clients/{client}', [ClientController::class, 'update'])->name('clients.update');
Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');

Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
Route::post('/vehicles', [VehicleController::class, 'store'])->name('vehicles.store');
Route::put('/vehicles/{vehicle}', [VehicleController::class, 'update'])->name('vehicles.update');
Route::delete('/vehicles/{vehicle}', [VehicleController::class, 'destroy'])->name('vehicles.destroy');

Route::get('/repairs', [RepairController::class, 'index'])->name('repairs.index');
Route::post('/repairs', [RepairController::class, 'store'])->name('repairs.store');
Route::put('/repairs/{repair}', [RepairController::class, 'update'])->name('repairs.update');
Route::delete('/repairs/{repair}', [RepairController::class, 'destroy'])->name('repairs.destroy');

Route::get('/delivery-notes', [DeliveryNoteController::class, 'index'])->name('delivery_notes.index');
Route::post('/delivery-notes', [DeliveryNoteController::class, 'store'])->name('delivery_notes.store');
Route::put('/delivery-notes/{deliveryNote}', [DeliveryNoteController::class, 'update'])->name('delivery_notes.update');
Route::delete('/delivery-notes/{deliveryNote}', [DeliveryNoteController::class, 'destroy'])->name('delivery_notes.destroy');

Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
Route::post('/families', [FamilyController::class, 'store'])->name('families.store');

Route::get('/insights', [InsightsController::class, 'index'])->name('insights.index');

Route::middleware(ProductionMiddleware::class)->group(function () {
    Route::post('/insights/query-openai', [InsightsController::class, 'getQueryResultsOpenAI'])->name('insights.query.openai');
});
Route::post('/insights/query-gemini', [InsightsController::class, 'getQueryResultsGeminiFlash'])->name('insights.query.gemini');

require __DIR__.'/auth.php';

<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\OrderManagementController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active'])->group(function () {

    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/stages/{stage}', [\App\Http\Controllers\DashboardController::class, 'showStage'])->name('dashboard.stage');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/order-stages/{orderStage}/start', [\App\Http\Controllers\OrderStageController::class, 'start'])->name('order-stages.start');
    Route::post('/order-stages/{orderStage}/pause', [\App\Http\Controllers\OrderStageController::class, 'pause'])->name('order-stages.pause');
    Route::post('/order-stages/{orderStage}/finish', [\App\Http\Controllers\OrderStageController::class, 'finish'])->name('order-stages.finish');
    Route::post('/order-stages/{orderStage}/remit', [\App\Http\Controllers\OrderStageController::class, 'remit'])->name('order-stages.remit');

    // Order Creation
    Route::get('/orders/create', [\App\Http\Controllers\OrderController::class, 'create'])->name('orders.create');
    Route::post('/orders', [\App\Http\Controllers\OrderController::class, 'store'])->name('orders.store');

    // Order Management
    Route::get('/orders', [OrderManagementController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}/edit', [OrderManagementController::class, 'edit'])->name('orders.edit');
    Route::put('/orders/{order}', [OrderManagementController::class, 'update'])->name('orders.update');
    Route::post('/orders/{order}/stages', [OrderManagementController::class, 'addStage'])->name('orders.add-stage');
    Route::delete('/orders/{order}/stages/{stage}', [OrderManagementController::class, 'removeStage'])->name('orders.remove-stage');

    // Clients Management
    Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
    Route::get('/clients/create', [ClientController::class, 'create'])->name('clients.create');
    Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
    Route::get('/clients/{client}/edit', [ClientController::class, 'edit'])->name('clients.edit');
    Route::put('/clients/{client}', [ClientController::class, 'update'])->name('clients.update');
    Route::get('/clients/search', [\App\Http\Controllers\ClientSearchController::class, 'search'])->name('clients.search');

});

require __DIR__.'/auth.php';

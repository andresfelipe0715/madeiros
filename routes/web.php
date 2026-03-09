<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\OrderManagementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active'])->group(function () {

    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/stages/{stage}', [\App\Http\Controllers\DashboardController::class, 'showStage'])->name('dashboard.stage');

    Route::post('/order-stages/{orderStage}/start', [\App\Http\Controllers\OrderStageController::class, 'start'])->name('order-stages.start');
    Route::post('/order-stages/{orderStage}/pause', [\App\Http\Controllers\OrderStageController::class, 'pause'])->name('order-stages.pause');
    Route::post('/order-stages/{orderStage}/finish', [\App\Http\Controllers\OrderStageController::class, 'finish'])->name('order-stages.finish');
    Route::post('/order-stages/{orderStage}/remit', [\App\Http\Controllers\OrderStageController::class, 'remit'])->name('order-stages.remit');
    Route::post('/order-stages/{orderStage}/notes', [\App\Http\Controllers\OrderStageController::class, 'updateNotes'])->name('order-stages.update-notes');
    Route::post('/order-stages/{orderStage}/deliver-hardware', [\App\Http\Controllers\OrderStageController::class, 'deliverHardware'])->name('order-stages.deliver-hardware');
    Route::post('/order-stages/{orderStage}/deliver-manual', [\App\Http\Controllers\OrderStageController::class, 'deliverManual'])->name('order-stages.deliver-manual');
    Route::post('/order-stages/{orderStage}/mark-as-pending', [\App\Http\Controllers\OrderStageController::class, 'markAsPending'])->name('order-stages.mark-as-pending');
    Route::post('/order-stages/{orderStage}/remove-pending', [\App\Http\Controllers\OrderStageController::class, 'removePending'])->name('order-stages.remove-pending');
    Route::post('/order-stages/{orderStage}/upload-evidence', [\App\Http\Controllers\OrderStageController::class, 'uploadEvidence'])->name('order-stages.upload-evidence');
    Route::delete('/order-files/{orderFile}', [\App\Http\Controllers\OrderStageController::class, 'deleteFile'])->name('order-files.destroy');

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

    // Generic Search
    Route::get('/materials/search', [\App\Http\Controllers\SearchController::class, 'materials'])->name('materials.search');
    Route::get('/special-services/search', [\App\Http\Controllers\SearchController::class, 'specialServices'])->name('special-services.search');

    // Users Management
    Route::resource('users', \App\Http\Controllers\UserController::class)->except(['show', 'destroy']);

    // Materials Management (POS Stock Only)
    Route::resource('materials', \App\Http\Controllers\MaterialController::class)->except(['show']);

    // Bodega Management (Bodega Stock Only)
    Route::get('bodega', [\App\Http\Controllers\BodegaController::class, 'index'])->name('bodega.index');
    Route::get('bodega/{material}/edit', [\App\Http\Controllers\BodegaController::class, 'edit'])->name('bodega.edit');
    Route::put('bodega/{material}', [\App\Http\Controllers\BodegaController::class, 'update'])->name('bodega.update');
    Route::post('bodega/{material}/transfer', [\App\Http\Controllers\BodegaController::class, 'transfer'])->name('bodega.transfer');

    // Special Services Management
    Route::resource('special-services', \App\Http\Controllers\SpecialServiceController::class)->except(['show']);

    // Performance Module
    Route::get('/performance', [\App\Http\Controllers\PerformanceController::class, 'index'])->name('performance.index');
    Route::get('/performance/details/{user}', [\App\Http\Controllers\PerformanceController::class, 'details'])->name('performance.details');

    // Material Consumption Calendar
    Route::get('/materials-consumption', [\App\Http\Controllers\MaterialConsumptionController::class, 'index'])->name('materials.consumption');
});

require __DIR__.'/auth.php';

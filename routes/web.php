<?php

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

});

require __DIR__ . '/auth.php';

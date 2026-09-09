<?php

use App\Http\Controllers\ImportController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\ReservationController;
use Illuminate\Support\Facades\Route;

Route::prefix('imports')->group(function () {
    Route::post('/', [ImportController::class, 'store'])->name('imports.store');
    Route::get('/{import}', [ImportController::class, 'show'])->name('imports.show');
});

Route::get('/properties', [PropertyController::class, 'index'])->name('properties.index');

Route::post('/offers/{offer}/reservations', [ReservationController::class, 'store'])->name('offers.reservations.store');

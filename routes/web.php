<?php

use App\Http\Controllers\DrawController;
use Illuminate\Support\Facades\Route;

// Ruta principal (opcional - redirige al listado de sorteos)
Route::get('/', function () {
    return redirect()->route('draws.index');
});

// Panel administrativo (SIN autenticación)
Route::prefix('admin')->group(function () {
    Route::get('/draws', [DrawController::class, 'index'])->name('draws.index');
    Route::get('/draws/create', [DrawController::class, 'create'])->name('draws.create');
    Route::post('/draws/upload', [DrawController::class, 'uploadExcel'])->name('draws.upload');
    Route::post('/draws', [DrawController::class, 'store'])->name('draws.store');
    Route::get('/draws/{draw}', [DrawController::class, 'show'])->name('draws.show');
    Route::post('/draws/{draw}/reset', [DrawController::class, 'reset'])->name('draws.reset');
    Route::post('/draws/{draw}/finish', [DrawController::class, 'finish'])->name('draws.finish');
    Route::delete('/draws/{draw}', [DrawController::class, 'destroy'])->name('draws.destroy');
});

// Pantalla pública del sorteo (para proyectar)
Route::get('/sorteo/{draw}', [DrawController::class, 'public'])->name('draws.public');

// API para el sorteo en tiempo real
Route::get('/api/draws/{draw}/participants', [DrawController::class, 'getParticipants'])->name('draws.participants');
Route::post('/api/draws/{draw}/perform', [DrawController::class, 'performDraw'])->name('draws.perform');

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentationController;

Route::get('/', function () {
    return view('welcome');
});

// API Documentation Routes
Route::get('/docs', [DocumentationController::class, 'index'])->name('api.documentation');
Route::get('/api/documentation/openapi.yaml', [DocumentationController::class, 'openapi'])->name('api.openapi');

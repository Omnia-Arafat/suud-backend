<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SwaggerController;

Route::get('/', function () {
    return view('welcome');
});

// Swagger Documentation Routes
Route::prefix('docs')->group(function () {
    Route::get('/', [SwaggerController::class, 'ui'])->name('swagger.ui');
    Route::get('/json', [SwaggerController::class, 'json'])->name('swagger.json');
    Route::get('/yaml', [SwaggerController::class, 'yaml'])->name('swagger.yaml');
});

// Alternative routes for easier access
Route::get('/documentation', [SwaggerController::class, 'ui'])->name('documentation');
Route::get('/api-docs', [SwaggerController::class, 'ui'])->name('api.docs');

// Postman collection download
Route::get('/docs/postman', function () {
    return response()->download(
        storage_path('api-docs/SUUD-API.postman_collection.json'),
        'SUUD-API.postman_collection.json',
        ['Content-Type' => 'application/json']
    );
})->name('postman.collection');

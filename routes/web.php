<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Return API information for root URL
Route::get('/', function () {
    return response()->json([
        'name' => 'BankManager API',
        'version' => '1.0.0',
        'environment' => app()->environment(),
        'documentation' => '/api/documentation',
        'endpoints' => [
            'api' => '/api/v1',
            'documentation' => '/api/documentation',
            'oauth' => '/api/oauth'
        ]
    ]);
});

// Route de connexion pour éviter l'erreur du middleware
Route::get('/login', function () {
    return response()->json(['message' => 'Veuillez utiliser l\'API pour vous connecter'], 401);
})->name('login');

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

Route::get('/', function () {
    return view('welcome');
});

// Route de connexion pour éviter l'erreur du middleware
Route::get('/login', function () {
    return response()->json(['message' => 'Veuillez utiliser l\'API pour vous connecter'], 401);
})->name('login');

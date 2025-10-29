<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\CompteController;
use App\Http\Controllers\Api\V1\TransactionController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Middleware\LoggingMiddleware;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Routes d'authentification publiques (supprimées car déplacées dans v1)

// API Version 1
Route::prefix('v1')->group(function () {

    // Routes d'authentification publiques dans v1
    Route::post('/login', [AuthController::class, 'login'])
        ->name('api.v1.login');

    // Routes d'authentification protégées dans v1
    Route::middleware('auth:api')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])
            ->name('api.v1.logout');
        Route::get('/user', [AuthController::class, 'user'])
            ->name('api.v1.user');
        Route::post('/refresh', [AuthController::class, 'refresh'])
            ->name('api.v1.refresh');
    });

    /*
    |--------------------------------------------------------------------------
    | Routes des Clients
    |--------------------------------------------------------------------------
    |
    | Routes pour la gestion des clients
    | Base URL: /api/v1
    |
    */

    Route::middleware([LoggingMiddleware::class])->group(function () {
        /**
         * Lister tous les clients
         *
         * Récupère la liste paginée des clients
         * Accessible uniquement aux administrateurs
         */
        Route::get('/clients', [ClientController::class, 'index'])
            ->name('api.v1.clients.index');

        /**
         * Créer un nouveau client
         *
         * Crée un client avec validation des données
         */
        Route::post('/clients', [ClientController::class, 'store'])
            ->name('api.v1.clients.store');

        /**
         * Récupérer un client spécifique
         */
        Route::get('/clients/{client}', [ClientController::class, 'show'])
            ->name('api.v1.clients.show');

        /**
         * Modifier un client
         */
        Route::put('/clients/{client}', [ClientController::class, 'update'])
            ->name('api.v1.clients.update');

        /**
         * Supprimer un client
         */
        Route::delete('/clients/{client}', [ClientController::class, 'destroy'])
            ->name('api.v1.clients.destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Routes des Comptes
    |--------------------------------------------------------------------------
    |
    | Routes pour la gestion des comptes bancaires
    | Base URL: /api/v1
    |
    */

    Route::middleware([LoggingMiddleware::class, 'request.logger'])->group(function () {
        /**
         * Lister tous les comptes
         *
         * Récupère la liste paginée des comptes non archivés
         * Accessible uniquement aux administrateurs
         */
        Route::get('/comptes', [CompteController::class, 'index'])
            ->name('api.v1.comptes.index');

        /**
         * Créer un nouveau compte
         *
         * Crée un compte bancaire avec un client (nouveau ou existant)
         * Envoie les notifications par email et SMS
         */
        Route::post('/comptes', [CompteController::class, 'store'])
            ->name('api.v1.comptes.store');

        /**
         * Récupérer un compte spécifique
         *
         * Admin peut récupérer un compte à partir de l'id
         * Client peut récupérer un de ses comptes par id
         */
        Route::get('/comptes/{compte}', [CompteController::class, 'show'])
            ->name('api.v1.comptes.show');

        /**
         * Routes pour les transactions
         */
        Route::apiResource('transactions', TransactionController::class)->only(['index', 'store', 'show']);

        /**
         * Bloquer un compte
         *
         * Bloque temporairement un compte avec motif et dates
         */
        Route::post('/comptes/{compte}/bloquer', [CompteController::class, 'bloquer'])
            ->name('api.v1.comptes.bloquer');
    });

    Route::middleware(['auth:api'])->group(function () {
        /**
         * Lister les comptes du client connecté
         *
         * Récupère la liste paginée des comptes du client authentifié
         */
        Route::get('/mes-comptes', [CompteController::class, 'mesComptes'])
            ->name('api.v1.comptes.mes-comptes');

        /**
         * Lister les comptes archivés (admin seulement)
         *
         * Récupère la liste des comptes archivés/bloqués
         */
        Route::get('/comptes-archives', [CompteController::class, 'comptesArchives'])
            ->name('api.v1.comptes.archives');
    });
});

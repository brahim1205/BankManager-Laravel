<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Services\ClientService;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
/**
 * @OA\Tag(
 *     name="Clients",
 *     description="Gestion des clients bancaires"
 * )
 */

class ClientController extends Controller
{
    use ApiResponseTrait;

    protected ClientService $clientService;

    public function __construct(ClientService $clientService)
    {
        $this->clientService = $clientService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $clients = $this->clientService->getAllClients($perPage);

            return $this->successResponse(
                $clients,
                'Liste des clients récupérée avec succès',
                200
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Erreur lors de la récupération des clients',
                500,
                $e->getMessage()
            );
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClientRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            // Ajouter un mot de passe par défaut si non fourni
            if (!isset($validated['password'])) {
                $validated['password'] = bcrypt('password123');
            } else {
                $validated['password'] = bcrypt($validated['password']);
            }

            $client = Client::create($validated);

            return $this->successResponse(
                'Client créé avec succès',
                $client->load('comptes'),
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Erreur lors de la création du client',
                500,
                $e->getMessage()
            );
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Client $client): JsonResponse
    {
        try {
            return $this->successResponse(
                'Client récupéré avec succès',
                $client->load(['comptes', 'comptes.transactions' => function($query) {
                    $query->latest()->take(5);
                }]),
                200
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Erreur lors de la récupération du client',
                500,
                $e->getMessage()
            );
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateClientRequest $request, Client $client): JsonResponse
    {
        try {
            $validated = $request->validated();

            $client->update($validated);

            return $this->successResponse(
                'Client mis à jour avec succès',
                $client->load('comptes'),
                200
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Erreur lors de la mise à jour du client',
                500,
                $e->getMessage()
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Client $client): JsonResponse
    {
        try {
            $this->clientService->deleteClient($client);

            return $this->successResponse(
                'Client supprimé avec succès',
                null,
                200
            );
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Erreur lors de la suppression du client',
                500,
                $e->getMessage()
            );
        }
    }
}

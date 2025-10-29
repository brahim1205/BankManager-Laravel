<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Support\Facades\Log;

class ClientService
{
    /**
     * Vérifier si un client peut être supprimé
     */
    public function canDeleteClient(Client $client): bool
    {
        return !$client->comptes()->where('statut', 'actif')->exists();
    }

    /**
     * Supprimer un client (soft delete)
     */
    public function deleteClient(Client $client): void
    {
        if (!$this->canDeleteClient($client)) {
            throw new \InvalidArgumentException('Impossible de supprimer un client avec des comptes actifs.');
        }

        // Soft delete du client
        $client->delete();

        // Soft delete de tous ses comptes
        $client->comptes()->delete();

        Log::info("Client supprimé (soft delete): {$client->numero}", [
            'client_id' => $client->id,
        ]);
    }

    /**
     * Récupérer tous les clients avec pagination
     */
    public function getAllClients(int $perPage = 15)
    {
        return Client::with('comptes')->paginate($perPage);
    }

    /**
     * Créer un client
     */
    public function createClient(array $data): Client
    {
        $client = Client::create($data);

        Log::info("Client créé: {$client->numero}", [
            'client_id' => $client->id,
            'email' => $client->email,
        ]);

        return $client;
    }

    /**
     * Mettre à jour un client
     */
    public function updateClient(Client $client, array $data): Client
    {
        $client->update($data);

        Log::info("Client mis à jour: {$client->numero}", [
            'client_id' => $client->id,
            'updated_fields' => array_keys($data),
        ]);

        return $client;
    }
}
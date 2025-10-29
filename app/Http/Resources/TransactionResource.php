<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero' => $this->numero,
            'type' => $this->type,
            'type_libelle' => $this->type_libelle,
            'montant' => $this->montant,
            'montant_formate' => $this->montant_formate,
            'devise' => $this->devise,
            'description' => $this->description,
            'statut' => $this->statut,
            'statut_libelle' => $this->statut_libelle,
            'date_transaction' => $this->date_transaction?->toISOString(),
            'compte_source' => $this->whenLoaded('compteSource', function () {
                return [
                    'id' => $this->compteSource->id,
                    'numero' => $this->compteSource->numero,
                    'libelle' => $this->compteSource->libelle,
                    'type' => $this->compteSource->type,
                    'client' => $this->whenLoaded('compteSource.client', function () {
                        return [
                            'id' => $this->compteSource->client->id,
                            'nom' => $this->compteSource->client->nom,
                            'prenom' => $this->compteSource->client->prenom,
                            'numero' => $this->compteSource->client->numero,
                        ];
                    }),
                ];
            }),
            'compte_destination' => $this->whenLoaded('compteDestination', function () {
                return [
                    'id' => $this->compteDestination->id,
                    'numero' => $this->compteDestination->numero,
                    'libelle' => $this->compteDestination->libelle,
                    'type' => $this->compteDestination->type,
                    'client' => $this->whenLoaded('compteDestination.client', function () {
                        return [
                            'id' => $this->compteDestination->client->id,
                            'nom' => $this->compteDestination->client->nom,
                            'prenom' => $this->compteDestination->client->prenom,
                            'numero' => $this->compteDestination->client->numero,
                        ];
                    }),
                ];
            }),
            'metadata' => [
                'created_at' => $this->created_at?->toISOString(),
                'updated_at' => $this->updated_at?->toISOString(),
                'version' => 1,
            ],
        ];
    }
}

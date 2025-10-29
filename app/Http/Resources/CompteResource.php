<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompteResource extends JsonResource
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
            'numeroCompte' => $this->numero,
            'titulaire' => $this->client->nom_complet,
            'type' => $this->type,
            'solde' => $this->solde,
            'devise' => $this->devise,
            'dateCreation' => $this->date_ouverture,
            'statut' => $this->statut,
            'motifBlocage' => $this->when($this->statut === 'bloque', $this->description),
            'metadata' => [
                'derniereModification' => $this->updated_at,
                'version' => 1
            ]
        ];
    }
}

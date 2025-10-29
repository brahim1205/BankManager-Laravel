<?php

namespace App\Services;

use App\Models\Compte;
use App\Models\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CompteService
{
    /**
     * Créer un compte avec client
     */
    public function createCompteWithClient(array $data): Compte
    {
        DB::beginTransaction();

        try {
            $clientData = $data['client'];
            $isNewClient = !isset($clientData['id']);

            if ($isNewClient) {
                $client = $this->createClient($clientData);
            } else {
                $client = Client::findOrFail($clientData['id']);
            }

            $compte = $this->createCompte([
                'libelle' => 'Compte ' . ucfirst($data['type']),
                'type' => $data['type'],
                'solde' => $data['soldeInitial'],
                'devise' => $data['devise'],
                'client_id' => $client->id,
                'statut' => 'actif',
            ]);

            DB::commit();

            // Déclencher l'événement
            \App\Events\CompteCreated::dispatch($compte, $client, $isNewClient);

            return $compte;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erreur lors de la création du compte: " . $e->getMessage(), [
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * Créer un client
     */
    private function createClient(array $data): Client
    {
        return Client::create([
            'numero' => 'CLI-' . strtoupper(\Illuminate\Support\Str::random(8)),
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'nci' => $data['nci'],
            'email' => $data['email'],
            'telephone' => $data['telephone'],
            'adresse' => $data['adresse'] ?? null,
            'password' => \Illuminate\Support\Str::random(10),
            'code_verification' => str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT),
        ]);
    }

    /**
     * Créer un compte
     */
    private function createCompte(array $data): Compte
    {
        return Compte::create($data);
    }

    /**
     * Mettre à jour un compte
     */
    public function updateCompte(string $compteId, array $data): Compte
    {
        DB::beginTransaction();

        try {
            $compte = Compte::with('client')->findOrFail($compteId);

            $updateData = [];
            $clientData = [];

            // Préparer les données de mise à jour
            if (isset($data['libelle'])) {
                $updateData['libelle'] = $data['libelle'];
            }

            if (isset($data['description'])) {
                $updateData['description'] = $data['description'];
            }

            if (isset($data['client']) && is_array($data['client'])) {
                $clientData = array_filter($data['client']);
            }

            // Mettre à jour le compte
            if (!empty($updateData)) {
                $compte->update($updateData);
            }

            // Mettre à jour le client
            if (!empty($clientData)) {
                $compte->client->update($clientData);
            }

            $compte->load('client');

            DB::commit();

            Log::info("Compte mis à jour: {$compte->numero}", [
                'compte_id' => $compte->id,
                'updated_fields' => array_keys($updateData),
            ]);

            return $compte;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erreur lors de la mise à jour du compte: " . $e->getMessage(), [
                'compte_id' => $compteId,
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * Bloquer un compte
     */
    public function bloquerCompte(string $compteId, array $data): Compte
    {
        $compte = Compte::findOrFail($compteId);

        $this->validateBlocage($compte, $data);

        $compte->update([
            'statut' => 'bloque',
            'date_debut_blocage' => $data['date_debut'],
            'date_fin_blocage' => $data['date_fin'],
            'motif_blocage' => $data['motif'],
        ]);

        Log::info("Compte bloqué: {$compte->numero}", [
            'compte_id' => $compte->id,
            'motif' => $data['motif'],
            'date_debut' => $data['date_debut'],
            'date_fin' => $data['date_fin'],
        ]);

        return $compte;
    }

    /**
     * Débloquer un compte
     */
    public function debloquerCompte(string $compteId): Compte
    {
        $compte = Compte::findOrFail($compteId);

        if ($compte->statut !== 'bloque') {
            throw new \InvalidArgumentException('Ce compte n\'est pas bloqué.');
        }

        $compte->update([
            'statut' => 'actif',
            'date_debut_blocage' => null,
            'date_fin_blocage' => null,
            'motif_blocage' => null,
        ]);

        Log::info("Compte débloqué: {$compte->numero}", [
            'compte_id' => $compte->id,
        ]);

        return $compte;
    }

    /**
     * Supprimer un compte (soft delete)
     */
    public function deleteCompte(string $compteId): Compte
    {
        $compte = Compte::findOrFail($compteId);

        $this->validateSuppression($compte);

        $compte->delete();

        Log::info("Compte supprimé (soft delete): {$compte->numero}", [
            'compte_id' => $compte->id,
        ]);

        return $compte;
    }

    /**
     * Validation pour blocage
     */
    private function validateBlocage(Compte $compte, array $data): void
    {
        if ($compte->statut === 'bloque') {
            throw new \InvalidArgumentException('Ce compte est déjà bloqué.');
        }

        if ($compte->archive) {
            throw new \InvalidArgumentException('Impossible de bloquer un compte archivé.');
        }

        // Vérifier que seul les comptes épargne peuvent être bloqués
        if ($compte->type !== 'epargne') {
            throw new \InvalidArgumentException('Seuls les comptes épargne peuvent être bloqués.');
        }

        // Validation des dates
        $dateDebut = \Carbon\Carbon::parse($data['date_debut']);
        $dateFin = \Carbon\Carbon::parse($data['date_fin']);
        $now = now();

        // La date de début doit être dans le futur ou aujourd'hui
        if ($dateDebut->lt($now->startOfDay())) {
            throw new \InvalidArgumentException('La date de début de blocage ne peut pas être dans le passé.');
        }

        // La date de fin doit être supérieure à la date de début
        if ($dateFin->lte($dateDebut)) {
            throw new \InvalidArgumentException('La date de fin de blocage doit être supérieure à la date de début.');
        }

        // La date de fin doit être dans le futur
        if ($dateFin->lt($now->startOfDay())) {
            throw new \InvalidArgumentException('La date de fin de blocage ne peut pas être dans le passé.');
        }
    }

    /**
     * Validation pour suppression
     */
    private function validateSuppression(Compte $compte): void
    {
        if ($compte->solde > 0) {
            throw new \InvalidArgumentException('Impossible de supprimer un compte avec un solde positif.');
        }
    }
}
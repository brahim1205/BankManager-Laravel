<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Compte;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionService
{
    /**
     * Valider les règles métier d'une transaction
     */
    public function validateBusinessRules(array $data): void
    {
        $type = $data['type'];
        $montant = $data['montant'];
        $compteSourceId = $data['compte_source_id'] ?? null;
        $compteDestinationId = $data['compte_destination_id'] ?? null;

        switch ($type) {
            case 'retrait':
                $this->validateRetrait($compteSourceId, $montant);
                break;

            case 'transfert':
            case 'virement':
                $this->validateTransfert($compteSourceId, $compteDestinationId, $montant);
                break;

            case 'depot':
                $this->validateDepot($compteDestinationId);
                break;
        }
    }

    /**
     * Validation pour retrait
     */
    private function validateRetrait(?string $compteSourceId, float $montant): void
    {
        if (!$compteSourceId) {
            throw new \InvalidArgumentException('Le compte source est requis pour un retrait.');
        }

        $compteSource = Compte::findOrFail($compteSourceId);

        if ($compteSource->statut !== 'actif') {
            throw new \InvalidArgumentException('Le compte source doit être actif.');
        }

        if ($compteSource->solde < $montant) {
            throw new \InvalidArgumentException('Solde insuffisant sur le compte source.');
        }
    }

    /**
     * Validation pour transfert/virement
     */
    private function validateTransfert(?string $compteSourceId, ?string $compteDestinationId, float $montant): void
    {
        if (!$compteSourceId || !$compteDestinationId) {
            throw new \InvalidArgumentException('Les comptes source et destination sont requis.');
        }

        $compteSource = Compte::findOrFail($compteSourceId);
        $compteDestination = Compte::findOrFail($compteDestinationId);

        if ($compteSource->statut !== 'actif' || $compteDestination->statut !== 'actif') {
            throw new \InvalidArgumentException('Les comptes source et destination doivent être actifs.');
        }

        if ($compteSource->solde < $montant) {
            throw new \InvalidArgumentException('Solde insuffisant sur le compte source.');
        }

        if ($compteSourceId === $compteDestinationId) {
            throw new \InvalidArgumentException('Les comptes source et destination doivent être différents.');
        }
    }

    /**
     * Validation pour dépôt
     */
    private function validateDepot(?string $compteDestinationId): void
    {
        if (!$compteDestinationId) {
            throw new \InvalidArgumentException('Le compte destination est requis pour un dépôt.');
        }

        $compteDestination = Compte::findOrFail($compteDestinationId);

        if ($compteDestination->statut !== 'actif') {
            throw new \InvalidArgumentException('Le compte destination doit être actif.');
        }
    }

    /**
     * Traiter une transaction (mise à jour des soldes)
     */
    public function processTransaction(Transaction $transaction): void
    {
        switch ($transaction->type) {
            case 'retrait':
                $transaction->compteSource->decrement('solde', $transaction->montant);
                break;

            case 'depot':
                $transaction->compteDestination->increment('solde', $transaction->montant);
                break;

            case 'transfert':
            case 'virement':
                $transaction->compteSource->decrement('solde', $transaction->montant);
                $transaction->compteDestination->increment('solde', $transaction->montant);
                break;
        }
    }

    /**
     * Créer une transaction avec validation et traitement
     */
    public function createTransaction(array $data): Transaction
    {
        DB::beginTransaction();

        try {
            // Validation des règles métier
            $this->validateBusinessRules($data);

            // Création de la transaction
            $transaction = Transaction::create([
                'numero' => 'TRX-' . strtoupper(uniqid()),
                'type' => $data['type'],
                'montant' => $data['montant'],
                'devise' => $data['devise'] ?? 'XOF',
                'description' => $data['description'] ?? null,
                'compte_source_id' => $data['compte_source_id'] ?? null,
                'compte_destination_id' => $data['compte_destination_id'] ?? null,
                'date_transaction' => $data['date_transaction'] ?? now(),
                'statut' => $data['statut'] ?? 'validee',
            ]);

            // Traitement de la transaction
            $this->processTransaction($transaction);

            DB::commit();

            Log::info("Transaction créée: {$transaction->numero}", [
                'transaction_id' => $transaction->id,
                'type' => $transaction->type,
                'montant' => $transaction->montant,
            ]);

            return $transaction;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erreur lors de la création de transaction: " . $e->getMessage(), [
                'data' => $data,
            ]);
            throw $e;
        }
    }
}
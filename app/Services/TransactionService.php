<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Compte;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionService
{


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
     * Créer une transaction avec traitement
     */
    public function createTransaction(array $data): Transaction
    {
        DB::beginTransaction();

        try {
            // Validation des fonds suffisants pour retraits et transferts
            if (in_array($data['type'], ['retrait', 'transfert', 'virement'])) {
                $compteSource = \App\Models\Compte::findOrFail($data['compte_source_id']);
                if (!$compteSource->peutDebiter($data['montant'])) {
                    throw new \InvalidArgumentException('Fonds insuffisants ou compte inactif.');
                }
            }

            // Validation du compte destination pour dépôts et transferts
            if (in_array($data['type'], ['depot', 'transfert', 'virement'])) {
                $compteDestination = \App\Models\Compte::findOrFail($data['compte_destination_id']);
                if ($compteDestination->statut !== 'actif') {
                    throw new \InvalidArgumentException('Compte destination inactif.');
                }
            }

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
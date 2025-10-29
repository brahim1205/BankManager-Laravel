<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UnarchiveExpiredBlockedAccounts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        \Illuminate\Support\Facades\Log::info('Démarrage du désarchivage des comptes bloqués expirés');

        // Récupérer tous les comptes bloqués dont la date de fin de blocage est dépassée
        $comptesADesarchiver = \App\Models\Compte::bloques()
            ->whereNotNull('date_fin_blocage')
            ->where('date_fin_blocage', '<=', now())
            ->where('archive', true)
            ->get();

        $comptesDesarchives = 0;
        $transactionsDesarchivees = 0;

        foreach ($comptesADesarchiver as $compte) {
            try {
                // Désarchiver le compte et le remettre en statut actif
                $compte->update([
                    'archive' => false,
                    'date_archivage' => null,
                    'statut' => 'actif', // Remettre en actif
                    'date_debut_blocage' => null,
                    'date_fin_blocage' => null,
                    'motif_blocage' => null,
                ]);

                // Désarchiver toutes les transactions liées à ce compte
                $transactions = \App\Models\Transaction::where(function($query) use ($compte) {
                    $query->where('compte_source_id', $compte->id)
                          ->orWhere('compte_destination_id', $compte->id);
                })
                ->where('archive', true)
                ->update([
                    'archive' => false,
                    'date_archivage' => null,
                ]);

                $transactionsDesarchivees += $transactions;
                $comptesDesarchives++;

                \Illuminate\Support\Facades\Log::info("Compte désarchivé: {$compte->numero} avec {$transactions} transactions");

            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Erreur lors du désarchivage du compte {$compte->numero}: " . $e->getMessage());
            }
        }

        \Illuminate\Support\Facades\Log::info("Désarchivage terminé: {$comptesDesarchives} comptes et {$transactionsDesarchivees} transactions désarchivés");
    }
}

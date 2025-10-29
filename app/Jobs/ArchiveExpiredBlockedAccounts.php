<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ArchiveExpiredBlockedAccounts implements ShouldQueue
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
        \Illuminate\Support\Facades\Log::info('Démarrage de l\'archivage des comptes bloqués expirés');

        // Récupérer tous les comptes bloqués dont la date de début de blocage est dépassée
        $comptesAArchiver = \App\Models\Compte::bloques()
            ->whereNotNull('date_debut_blocage')
            ->where('date_debut_blocage', '<=', now())
            ->where('archive', false)
            ->get();

        $comptesArchives = 0;
        $transactionsArchivees = 0;

        foreach ($comptesAArchiver as $compte) {
            try {
                // Archiver le compte
                $compte->update([
                    'archive' => true,
                    'date_archivage' => now(),
                    'statut' => 'ferme', // Fermer définitivement le compte
                ]);

                // Archiver toutes les transactions liées à ce compte
                $transactions = \App\Models\Transaction::where(function($query) use ($compte) {
                    $query->where('compte_source_id', $compte->id)
                          ->orWhere('compte_destination_id', $compte->id);
                })
                ->where('archive', false)
                ->update([
                    'archive' => true,
                    'date_archivage' => now(),
                ]);

                $transactionsArchivees += $transactions;
                $comptesArchives++;

                \Illuminate\Support\Facades\Log::info("Compte archivé: {$compte->numero} avec {$transactions} transactions");

            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Erreur lors de l'archivage du compte {$compte->numero}: " . $e->getMessage());
            }
        }

        \Illuminate\Support\Facades\Log::info("Archivage terminé: {$comptesArchives} comptes et {$transactionsArchivees} transactions archivés");
    }
}

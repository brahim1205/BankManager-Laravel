<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\ClientCredentialsMail;
use App\Services\SmsService;

class SendClientNotification implements ShouldQueue
{
    use InteractsWithQueue;

    protected $smsService;

    /**
     * Create the event listener.
     */
    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Handle the event.
     */
    public function handle(\App\Events\CompteCreated $event): void
    {
        $client = $event->client;
        $compte = $event->compte;

        try {
            // Envoyer l'email avec les identifiants
            if ($event->isNewClient) {
                Mail::to($client->email)->send(new ClientCredentialsMail($client, $compte));
                Log::info('Email d\'authentification envoyé', [
                    'client_id' => $client->id,
                    'email' => $client->email
                ]);
            }

            // Envoyer le SMS avec le code de vérification
            $message = "Bienvenue chez Banque Example. Votre code de vérification est: {$client->code_verification}";
            $this->smsService->sendSms($client->telephone, $message);

            Log::info('SMS de vérification envoyé', [
                'client_id' => $client->id,
                'telephone' => $client->telephone
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi des notifications client', [
                'client_id' => $client->id,
                'error' => $e->getMessage()
            ]);

            // Re-throw pour que le job soit marqué comme échoué
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\App\Events\CompteCreated $event, \Throwable $exception): void
    {
        Log::error('Échec de l\'envoi des notifications client', [
            'client_id' => $event->client->id,
            'error' => $exception->getMessage()
        ]);
    }
}

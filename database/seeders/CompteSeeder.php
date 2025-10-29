<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Client;
use App\Models\Compte;

class CompteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer tous les clients existants
        $clients = Client::all();

        if ($clients->isEmpty()) {
            // Si pas de clients, en créer quelques-uns
            $clients = Client::factory(5)->create();
        }

        // Créer des comptes pour chaque client
        foreach ($clients as $client) {
            // Chaque client a entre 1 et 3 comptes
            $nombreComptes = rand(1, 3);

            for ($i = 0; $i < $nombreComptes; $i++) {
                Compte::factory()->create([
                    'client_id' => $client->id,
                ]);
            }
        }
    }
}

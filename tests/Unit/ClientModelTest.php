<?php

namespace Tests\Unit;

use App\Models\Client;
use App\Models\Compte;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_has_uuid_primary_key()
    {
        $client = Client::factory()->create();

        $this->assertNotNull($client->id);
        $this->assertIsString($client->id);
        $this->assertEquals(36, strlen($client->id)); // UUID v4 length
    }

    public function test_client_has_fillable_attributes()
    {
        $data = [
            'numero' => 'CLI-TEST123',
            'nom' => 'Dupont',
            'prenom' => 'Jean',
            'email' => 'jean.dupont@test.com',
            'telephone' => '+221771234567',
        ];

        $client = Client::create($data);

        $this->assertEquals($data['numero'], $client->numero);
        $this->assertEquals($data['nom'], $client->nom);
        $this->assertEquals($data['prenom'], $client->prenom);
        $this->assertEquals($data['email'], $client->email);
        $this->assertEquals($data['telephone'], $client->telephone);
    }

    public function test_client_generates_numero_automatically()
    {
        $client = Client::factory()->create(['numero' => null]);

        $this->assertNotNull($client->numero);
        $this->assertStringStartsWith('CLI-', $client->numero);
        $this->assertEquals(12, strlen($client->numero)); // CLI- + 8 chars
    }

    public function test_client_has_nom_complet_accessor()
    {
        $client = Client::factory()->create([
            'prenom' => 'Marie',
            'nom' => 'Dubois'
        ]);

        $this->assertEquals('Marie Dubois', $client->nom_complet);
    }

    public function test_client_has_comptes_relationship()
    {
        $client = Client::factory()->create();
        $compte = Compte::factory()->create(['client_id' => $client->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $client->comptes());
        $this->assertCount(1, $client->comptes);
        $this->assertEquals($compte->id, $client->comptes->first()->id);
    }

    public function test_client_has_nombre_comptes_accessor()
    {
        $client = Client::factory()->create();
        Compte::factory()->count(3)->create(['client_id' => $client->id]);

        $this->assertEquals(3, $client->nombre_comptes);
    }

    public function test_client_has_solde_total_accessor()
    {
        $client = Client::factory()->create();
        Compte::factory()->create(['client_id' => $client->id, 'solde' => 1000]);
        Compte::factory()->create(['client_id' => $client->id, 'solde' => 2500]);

        $this->assertEquals(3500, $client->solde_total);
    }

    public function test_client_casts_id_as_string()
    {
        $client = Client::factory()->create();

        $this->assertIsString($client->getAttributes()['id']);
    }

    public function test_client_uses_uuids_trait()
    {
        $client = new Client();

        $this->assertContains('HasUuids', class_uses_recursive($client));
    }
}

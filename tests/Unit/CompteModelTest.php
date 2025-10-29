<?php

namespace Tests\Unit;

use App\Models\Compte;
use App\Models\Client;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompteModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_compte_has_uuid_primary_key()
    {
        $compte = Compte::factory()->create();

        $this->assertNotNull($compte->id);
        $this->assertIsString($compte->id);
        $this->assertEquals(36, strlen($compte->id));
    }

    public function test_compte_generates_numero_automatically()
    {
        $compte = Compte::factory()->create(['numero' => null]);

        $this->assertNotNull($compte->numero);
        $this->assertStringStartsWith('CC-', $compte->numero); // Compte courant par défaut
    }

    public function test_compte_generates_different_numero_prefixes()
    {
        $courant = Compte::factory()->create(['type' => 'courant']);
        $epargne = Compte::factory()->create(['type' => 'epargne']);
        $entreprise = Compte::factory()->create(['type' => 'entreprise']);
        $joint = Compte::factory()->create(['type' => 'joint']);

        $this->assertStringStartsWith('CC-', $courant->numero);
        $this->assertStringStartsWith('CE-', $epargne->numero);
        $this->assertStringStartsWith('CE-', $entreprise->numero);
        $this->assertStringStartsWith('CJ-', $joint->numero);
    }

    public function test_compte_has_client_relationship()
    {
        $client = Client::factory()->create();
        $compte = Compte::factory()->create(['client_id' => $client->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $compte->client());
        $this->assertEquals($client->id, $compte->client->id);
    }

    public function test_compte_has_transactions_relationships()
    {
        $compte = Compte::factory()->create();

        // Transaction sortante
        Transaction::factory()->create([
            'compte_source_id' => $compte->id,
            'compte_destination_id' => Compte::factory()->create()->id
        ]);

        // Transaction entrante
        Transaction::factory()->create([
            'compte_source_id' => Compte::factory()->create()->id,
            'compte_destination_id' => $compte->id
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $compte->transactionsSource());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $compte->transactionsDestination());
        $this->assertCount(2, $compte->transactions);
    }

    public function test_compte_has_solde_formate_accessor()
    {
        $compte = Compte::factory()->create(['solde' => 1234.56]);

        $this->assertEquals('1 234,56 XOF', $compte->solde_formate);
    }

    public function test_compte_has_type_libelle_accessor()
    {
        $courant = Compte::factory()->create(['type' => 'courant']);
        $epargne = Compte::factory()->create(['type' => 'epargne']);

        $this->assertEquals('Compte Courant', $courant->type_libelle);
        $this->assertEquals('Compte Épargne', $epargne->type_libelle);
    }

    public function test_compte_has_statut_libelle_accessor()
    {
        $actif = Compte::factory()->create(['statut' => 'actif']);
        $bloque = Compte::factory()->create(['statut' => 'bloque']);

        $this->assertEquals('Actif', $actif->statut_libelle);
        $this->assertEquals('Bloqué', $bloque->statut_libelle);
    }

    public function test_compte_peut_debiter_method()
    {
        $actif = Compte::factory()->create(['statut' => 'actif', 'solde' => 1000]);
        $bloque = Compte::factory()->create(['statut' => 'bloque', 'solde' => 1000]);
        $insuffisant = Compte::factory()->create(['statut' => 'actif', 'solde' => 100]);

        $this->assertTrue($actif->peutDebiter(500));
        $this->assertFalse($bloque->peutDebiter(500));
        $this->assertFalse($insuffisant->peutDebiter(200));
    }

    public function test_compte_debiter_method()
    {
        $compte = Compte::factory()->create(['statut' => 'actif', 'solde' => 1000]);

        $result = $compte->debiter(300);
        $compte->refresh();

        $this->assertTrue($result);
        $this->assertEquals(700, $compte->solde);
    }

    public function test_compte_crediter_method()
    {
        $compte = Compte::factory()->create(['statut' => 'actif', 'solde' => 1000]);

        $result = $compte->crediter(500);
        $compte->refresh();

        $this->assertTrue($result);
        $this->assertEquals(1500, $compte->solde);
    }

    public function test_compte_scopes()
    {
        Compte::factory()->create(['statut' => 'actif']);
        Compte::factory()->create(['statut' => 'bloque']);
        Compte::factory()->create(['type' => 'courant']);
        Compte::factory()->create(['type' => 'epargne']);

        $this->assertCount(1, Compte::actifs()->get());
        $this->assertCount(1, Compte::parType('courant')->get());
    }

    public function test_compte_casts()
    {
        $compte = Compte::factory()->create();

        $this->assertIsString($compte->getAttributes()['id']);
        $this->assertIsFloat($compte->getAttributes()['solde']);
        $this->assertIsString($compte->getAttributes()['client_id']);
    }
}

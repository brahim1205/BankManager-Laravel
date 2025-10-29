<?php

namespace Tests\Unit;

use App\Models\Transaction;
use App\Models\Compte;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_has_uuid_primary_key()
    {
        $transaction = Transaction::factory()->create();

        $this->assertNotNull($transaction->id);
        $this->assertIsString($transaction->id);
        $this->assertEquals(36, strlen($transaction->id));
    }

    public function test_transaction_generates_numero_automatically()
    {
        $transaction = Transaction::factory()->create(['numero' => null]);

        $this->assertNotNull($transaction->numero);
        $this->assertStringStartsWith('TRX-', $transaction->numero);
        $this->assertEquals(14, strlen($transaction->numero)); // TRX- + 10 chars
    }

    public function test_transaction_has_compte_relationships()
    {
        $compteSource = Compte::factory()->create();
        $compteDestination = Compte::factory()->create();

        $transaction = Transaction::factory()->create([
            'compte_source_id' => $compteSource->id,
            'compte_destination_id' => $compteDestination->id
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $transaction->compteSource());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $transaction->compteDestination());

        $this->assertEquals($compteSource->id, $transaction->compteSource->id);
        $this->assertEquals($compteDestination->id, $transaction->compteDestination->id);
    }

    public function test_transaction_has_montant_formate_accessor()
    {
        $transaction = Transaction::factory()->create(['montant' => 1234.56]);

        $this->assertEquals('1 234,56 XOF', $transaction->montant_formate);
    }

    public function test_transaction_has_type_libelle_accessor()
    {
        $depot = Transaction::factory()->create(['type' => 'depot']);
        $retrait = Transaction::factory()->create(['type' => 'retrait']);
        $transfert = Transaction::factory()->create(['type' => 'transfert']);

        $this->assertEquals('Dépôt', $depot->type_libelle);
        $this->assertEquals('Retrait', $retrait->type_libelle);
        $this->assertEquals('Transfert', $transfert->type_libelle);
    }

    public function test_transaction_has_statut_libelle_accessor()
    {
        $validee = Transaction::factory()->create(['statut' => 'validee']);
        $enAttente = Transaction::factory()->create(['statut' => 'en_attente']);
        $rejete = Transaction::factory()->create(['statut' => 'rejete']);

        $this->assertEquals('Validée', $validee->statut_libelle);
        $this->assertEquals('En attente', $enAttente->statut_libelle);
        $this->assertEquals('Rejetée', $rejete->statut_libelle);
    }

    public function test_transaction_scopes()
    {
        Transaction::factory()->create(['statut' => 'validee']);
        Transaction::factory()->create(['statut' => 'en_attente']);
        Transaction::factory()->create(['type' => 'depot']);
        Transaction::factory()->create(['type' => 'retrait']);

        $this->assertCount(1, Transaction::validees()->get());
        $this->assertCount(1, Transaction::enAttente()->get());
        $this->assertCount(1, Transaction::parType('depot')->get());
    }

    public function test_transaction_scope_par_compte()
    {
        $compte1 = Compte::factory()->create();
        $compte2 = Compte::factory()->create();

        // Transaction où compte1 est source
        Transaction::factory()->create([
            'compte_source_id' => $compte1->id,
            'compte_destination_id' => $compte2->id
        ]);

        // Transaction où compte1 est destination
        Transaction::factory()->create([
            'compte_source_id' => $compte2->id,
            'compte_destination_id' => $compte1->id
        ]);

        // Transaction sans rapport avec compte1
        Transaction::factory()->create([
            'compte_source_id' => $compte2->id,
            'compte_destination_id' => Compte::factory()->create()->id
        ]);

        $transactionsCompte1 = Transaction::parCompte($compte1->id)->get();

        $this->assertCount(2, $transactionsCompte1);
    }

    public function test_transaction_casts()
    {
        $transaction = Transaction::factory()->create();

        $this->assertIsString($transaction->getAttributes()['id']);
        $this->assertIsFloat($transaction->getAttributes()['montant']);
        $this->assertIsString($transaction->getAttributes()['compte_source_id']);
        $this->assertIsString($transaction->getAttributes()['compte_destination_id']);
    }

    public function test_transaction_sets_date_transaction_automatically()
    {
        $transaction = Transaction::factory()->create(['date_transaction' => null]);

        $this->assertNotNull($transaction->date_transaction);
        $this->assertInstanceOf(\Carbon\Carbon::class, $transaction->date_transaction);
    }

    public function test_transaction_uses_uuids_trait()
    {
        $transaction = new Transaction();

        $this->assertContains('HasUuids', class_uses_recursive($transaction));
    }
}

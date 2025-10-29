<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('comptes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('numero')->unique();
            $table->string('libelle');
            $table->enum('type', ['courant', 'epargne', 'entreprise', 'joint']);
            $table->decimal('solde', 15, 2)->default(0);
            $table->string('devise', 3)->default('XOF');
            $table->uuid('client_id');
            $table->date('date_ouverture');
            $table->enum('statut', ['actif', 'bloque', 'ferme'])->default('actif');
            $table->text('description')->nullable();
            $table->timestamp('date_debut_blocage')->nullable();
            $table->timestamp('date_fin_blocage')->nullable();
            $table->text('motif_blocage')->nullable();
            $table->boolean('archive')->default(false);
            $table->timestamp('date_archivage')->nullable();
            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');

            $table->index(['client_id', 'type']);
            $table->index(['statut', 'date_ouverture']);
            $table->index('numero');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comptes');
    }
};

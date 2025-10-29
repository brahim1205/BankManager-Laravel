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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('numero')->unique();
            $table->enum('type', ['depot', 'retrait', 'transfert', 'virement']);
            $table->decimal('montant', 15, 2);
            $table->string('devise', 3)->default('XOF');
            $table->text('description')->nullable();
            $table->uuid('compte_source_id')->nullable();
            $table->uuid('compte_destination_id')->nullable();
            $table->timestamp('date_transaction');
            $table->enum('statut', ['en_attente', 'validee', 'rejete'])->default('en_attente');
            $table->boolean('archive')->default(false);
            $table->timestamp('date_archivage')->nullable();
            $table->timestamps();

            $table->foreign('compte_source_id')->references('id')->on('comptes')->onDelete('cascade');
            $table->foreign('compte_destination_id')->references('id')->on('comptes')->onDelete('cascade');

            $table->index(['compte_source_id', 'date_transaction']);
            $table->index(['compte_destination_id', 'date_transaction']);
            $table->index(['type', 'statut']);
            $table->index('numero');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

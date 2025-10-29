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
        Schema::create('clients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('numero')->unique();
            $table->string('nom');
            $table->string('prenom');
            $table->string('nci')->nullable()->unique();
            $table->string('email')->unique();
            $table->string('telephone')->nullable()->unique();
            $table->text('adresse')->nullable();
            $table->string('password');
            $table->string('code_verification')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();

            $table->index(['nom', 'prenom']);
            $table->index('email');
            $table->index('telephone');
            $table->index('nci');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};

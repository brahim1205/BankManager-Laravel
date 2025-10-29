<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer un utilisateur admin
        User::create([
            'name' => 'Admin BankManager',
            'email' => 'admin@bankmanager.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // Créer quelques utilisateurs de test
        User::factory(5)->create();
    }
}

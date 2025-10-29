<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['depot', 'retrait', 'transfert', 'virement'];
        $type = $this->faker->randomElement($types);

        return [
            'numero' => 'TRX-' . strtoupper($this->faker->unique()->bothify('??????????')),
            'type' => $type,
            'montant' => $this->faker->randomFloat(2, 1000, 1000000),
            'devise' => 'XOF',
            'description' => $this->faker->sentence(),
            'date_transaction' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'statut' => $this->faker->randomElement(['en_attente', 'validee', 'rejete']),
        ];
    }

    public function validee()
    {
        return $this->state(fn (array $attributes) => [
            'statut' => 'validee',
        ]);
    }

    public function enAttente()
    {
        return $this->state(fn (array $attributes) => [
            'statut' => 'en_attente',
        ]);
    }

    public function depot()
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'depot',
        ]);
    }

    public function retrait()
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'retrait',
        ]);
    }
}

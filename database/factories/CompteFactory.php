<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Compte>
 */
class CompteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['courant', 'epargne', 'entreprise', 'joint'];
        $type = $this->faker->randomElement($types);

        $prefix = match($type) {
            'courant' => 'CC',
            'epargne' => 'CE',
            'entreprise' => 'CE',
            'joint' => 'CJ',
            default => 'C'
        };

        return [
            'numero' => $prefix . '-' . strtoupper($this->faker->unique()->bothify('??????????')),
            'libelle' => $this->faker->randomElement([
                'Compte Principal',
                'Compte Épargne',
                'Compte Entreprise',
                'Compte Joint',
                'Compte Secondaire'
            ]),
            'type' => $type,
            'solde' => $this->faker->randomFloat(2, 0, 1000000),
            'devise' => 'XOF',
            'date_ouverture' => $this->faker->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
            'statut' => $this->faker->randomElement(['actif', 'bloque', 'ferme']),
            'description' => $this->faker->optional(0.7)->sentence(),
        ];
    }

    public function actif()
    {
        return $this->state(fn (array $attributes) => [
            'statut' => 'actif',
        ]);
    }

    public function courant()
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'courant',
        ]);
    }

    public function epargne()
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'epargne',
        ]);
    }

    public function entreprise()
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'entreprise',
        ]);
    }
}

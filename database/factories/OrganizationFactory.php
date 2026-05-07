<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'is_default' => false,
        ];
    }

    /**
     * Configure the organization as the default organization.
     */
    public function default(): static
    {
        return $this->state(fn () => [
            'name' => 'Default',
            'is_default' => true,
        ]);
    }
}

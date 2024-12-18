<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TrackableSchema>
 */
class TrackableSchemaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(2),
            'field_type' => fake()->randomElement(['int', 'float', 'string']),
            'required' => (mt_rand(1, 100) <= 80) ? 1 : 0,
            'validation_rule' => '[]'
        ];
    }
}

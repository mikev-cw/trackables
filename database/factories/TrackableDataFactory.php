<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TrackableData>
 */
class TrackableDataFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
        ];
    }

    public function int(): static
    {
        return $this->state(fn (array $attributes) => [
            'value' => $this->faker->randomNumber(),
        ]);
    }

    public function float(): static
    {
        return $this->state(fn (array $attributes) => [
            'value' => $this->faker->randomFloat(),
        ]);
    }

    public function string(): static
    {
        return $this->state(fn (array $attributes) => [
            'value' => $this->faker->word()
        ]);
    }
}

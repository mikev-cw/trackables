<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Trackable>
 */
class TrackableFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(6),
            'user_id' => 1,
            'deleted' => 0
        ];
    }

    public function deleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'deleted' => 1,
        ]);
    }

    public function otherUsers(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => fake()->numberBetween(2,5),
        ]);
    }
}

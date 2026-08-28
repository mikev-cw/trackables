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
        $fieldType = fake()->randomElement(['int', 'float', 'string']);

        return [
            'name' => fake()->sentence(2),
            'field_type' => $fieldType,
            'validation_config' => [
                'required' => false,
                'min' => null,
                'max' => null,
                'max_length' => $fieldType === 'string' ? 255 : null,
                'format' => null,
            ],
        ];
    }
}

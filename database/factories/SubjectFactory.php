<?php

namespace Database\Factories;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subject>
 */
class SubjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('???####')),
            'name' => ucwords(fake()->unique()->words(3, true)),
            'credit_hours' => fake()->numberBetween(2, 4),
        ];
    }
}

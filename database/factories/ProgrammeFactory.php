<?php

namespace Database\Factories;

use App\Models\Programme;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Programme>
 */
class ProgrammeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('???##')),
            'name' => 'Diploma in '.fake()->unique()->words(2, true),
            'level' => fake()->randomElement(Programme::LEVELS),
        ];
    }
}

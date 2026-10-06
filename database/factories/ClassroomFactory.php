<?php

namespace Database\Factories;

use App\Models\Classroom;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Classroom>
 */
class ClassroomFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('RM-###')),
            'name' => 'Room '.fake()->unique()->numberBetween(1, 999),
            'building' => null,
            'type' => 'Lecture room',
            'capacity' => 40,
        ];
    }
}

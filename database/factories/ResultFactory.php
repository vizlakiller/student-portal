<?php

namespace Database\Factories;

use App\Models\Result;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Result>
 */
class ResultFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'subject_id' => Subject::factory(),
            'semester' => fake()->numberBetween(1, 6),
            'marks' => fake()->numberBetween(30, 100),
        ];
    }
}

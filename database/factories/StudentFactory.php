<?php

namespace Database\Factories;

use App\Models\Programme;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_no' => strtoupper(fake()->unique()->bothify('STU#######')),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('01#-#######'),
            'gender' => fake()->randomElement(Student::GENDERS),
            'date_of_birth' => fake()->dateTimeBetween('-25 years', '-18 years')->format('Y-m-d'),
            'address' => fake()->address(),
            'programme_id' => Programme::factory(),
            'intake_year' => fake()->numberBetween(2022, 2026),
            'semester' => fake()->numberBetween(1, 6),
            'status' => 'Active',
        ];
    }
}

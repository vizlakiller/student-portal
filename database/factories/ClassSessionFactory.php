<?php

namespace Database\Factories;

use App\Models\ClassSession;
use App\Models\Subject;
use App\Models\Term;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassSession>
 */
class ClassSessionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'term_id' => Term::factory()->current(),
            'subject_id' => Subject::factory(),
            'name' => 'Group '.fake()->unique()->bothify('?#'),
            'capacity' => null,
        ];
    }
}

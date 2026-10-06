<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('D????')),
            'name' => 'Department of '.ucfirst(fake()->unique()->word()),
            'hod_id' => null,
        ];
    }
}

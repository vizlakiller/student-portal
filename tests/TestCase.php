<?php

namespace Tests;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Log in as a new user with the given role, e.g. $this->actingAsRole('registrar').
     */
    protected function actingAsRole(string $role): User
    {
        $user = User::factory()->role($role)->create();
        $this->actingAs($user);

        return $user;
    }

    /**
     * Log in as a new head of department who heads a new department.
     *
     * @return array{0: User, 1: Department}
     */
    protected function actingAsHod(): array
    {
        $hod = User::factory()->role('hod')->create();
        $department = Department::factory()->create(['hod_id' => $hod->id]);
        $this->actingAs($hod);

        return [$hod, $department];
    }
}

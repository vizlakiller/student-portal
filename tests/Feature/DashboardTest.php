<?php

namespace Tests\Feature;

use App\Models\Result;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_loads_with_an_empty_database(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')->assertOk()->assertSee('No results recorded yet');
    }

    public function test_dashboard_loads_with_sample_data(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->actingAs(User::first())
            ->get('/dashboard')->assertOk()->assertSee('Students by programme')->assertSee('DCS');
    }

    public function test_seeder_can_run_twice_without_duplicates(): void
    {
        $this->seed(DatabaseSeeder::class);
        $count = Result::count();

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($count, Result::count());
        $this->assertSame(2, User::count());
    }
}

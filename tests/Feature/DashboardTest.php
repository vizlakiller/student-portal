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
        $this->actingAsRole('registrar');
        $this->get('/dashboard')->assertOk()->assertSee('No results recorded yet');
    }

    public function test_each_role_lands_on_the_right_page(): void
    {
        $this->actingAsRole('accountant');
        $this->get('/dashboard')->assertRedirect('/finance');

        $this->actingAsRole('teacher');
        $this->get('/dashboard')->assertOk()->assertSee('My subjects');

        $this->actingAsRole('admin_staff');
        $this->get('/dashboard')->assertOk()->assertSee('Students by programme')->assertDontSee('Grade distribution');

        $this->actingAsRole('hod');
        $this->get('/dashboard')->assertOk()->assertSee('Grade distribution');
    }

    public function test_every_demo_account_can_open_its_dashboard_with_sample_data(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach (['admin', 'adminstaff', 'hod', 'registrar', 'teacher'] as $name) {
            $this->actingAs(User::where('email', "{$name}@example.com")->first())
                ->get('/dashboard')->assertOk();
        }

        $this->actingAs(User::where('email', 'teacher@example.com')->first())
            ->get('/dashboard')->assertSee('Waiting for your approval');

        $this->actingAs(User::where('email', 'accountant@example.com')->first())
            ->get('/finance')->assertOk()->assertSee('Owed by');
    }

    public function test_seeder_can_run_twice_without_duplicates(): void
    {
        $this->seed(DatabaseSeeder::class);
        $counts = [Result::count(), User::count(), \App\Models\Charge::count(), \App\Models\Payment::count()];

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($counts, [Result::count(), User::count(), \App\Models\Charge::count(), \App\Models\Payment::count()]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Charge;
use App\Models\ClassSession;
use App\Models\Department;
use App\Models\Payment;
use App\Models\Result;
use App\Models\TimetableSlot;
use App\Models\User;
use App\Support\Timetable;
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

        $this->actingAsRole('management');
        $this->get('/dashboard')->assertRedirect('/statistics');

        $this->actingAsRole('lecturer');
        $this->get('/dashboard')->assertOk()->assertSee('My sessions');

        $this->actingAsRole('admin_staff');
        $this->get('/dashboard')->assertOk()->assertSee('Students by programme')->assertDontSee('Grade distribution');

        $this->actingAsRole('hod');
        $this->get('/dashboard')->assertOk()->assertSee('Grade distribution');
    }

    public function test_every_demo_account_can_open_its_pages_with_sample_data(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach (['admin', 'adminstaff', 'hod', 'hod2', 'registrar', 'lecturer', 'lecturer2', 'lecturer3', 'lecturer4'] as $name) {
            $user = User::where('email', "{$name}@example.com")->firstOrFail();
            $this->actingAs($user)->get('/dashboard')->assertOk();
            $this->get('/timetable')->assertOk();
        }

        $this->actingAs(User::where('email', 'lecturer@example.com')->first())
            ->get('/dashboard')->assertSee('Waiting for your approval');
        $this->get('/my-timetable')->assertOk()->assertSee('CSC1043');

        $this->actingAs(User::where('email', 'director@example.com')->first())
            ->get('/statistics')->assertOk()->assertSee('Collection rate');

        $this->actingAs(User::where('email', 'accountant@example.com')->first())
            ->get('/finance')->assertOk()->assertSee('Owed by');

        // Every sample department except one has a head; hod2 heads two.
        $this->assertSame(2, User::where('email', 'hod2@example.com')->first()->departments()->count());
        $this->assertSame(1, Department::whereNull('hod_id')->count());
    }

    public function test_sample_timetable_has_no_clashes_and_every_waiting_student_has_a_session(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach (ClassSession::with('slots', 'lecturers', 'subject')->get() as $session) {
            foreach ($session->slots as $slot) {
                $clashes = Timetable::clashesFor($session, $slot->day, $slot->starts_at, $slot->ends_at, $slot->classroom_id, $slot->id);
                $this->assertSame([], $clashes, "Clash for {$session->label()}");
            }
        }

        $this->assertGreaterThan(10, TimetableSlot::count());
        $this->assertSame(0, Result::whereNull('marks')->whereNull('class_session_id')->count());
    }

    public function test_seeder_can_run_twice_without_duplicates(): void
    {
        $this->seed(DatabaseSeeder::class);
        $count = fn () => [Result::count(), User::count(), Charge::count(), Payment::count(), Department::count(), ClassSession::count(), TimetableSlot::count()];
        $before = $count();

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($before, $count());
    }
}

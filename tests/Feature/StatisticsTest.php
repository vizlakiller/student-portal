<?php

namespace Tests\Feature;

use App\Models\Charge;
use App\Models\ClassSession;
use App\Models\Classroom;
use App\Models\Payment;
use App\Models\Result;
use App\Models\Student;
use App\Models\Term;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Management (Director / COO / CEO): figures for the whole college, read-only.
 */
class StatisticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_statistics_page_works_with_an_empty_database(): void
    {
        $this->actingAsRole('management');

        $this->get('/statistics')->assertOk()
            ->assertSee('No current term is set')
            ->assertSee('No marks recorded yet')
            ->assertSee('Nobody owes money');
    }

    public function test_figures_add_up(): void
    {
        $term = Term::factory()->current()->create(['name' => 'September 2026']);
        $students = Student::factory()->count(2)->create();

        // Two marked results (one pass, one fail) and one waiting for marks this term.
        Result::factory()->for($students[0])->create(['marks' => 80]);
        Result::factory()->for($students[1])->create(['marks' => 20]);
        $session = ClassSession::factory()->create(['term_id' => $term->id]);
        Result::factory()->for($students[0])->create(['subject_id' => $session->subject_id, 'class_session_id' => $session->id, 'marks' => null]);
        $session->slots()->create(['day' => 1, 'starts_at' => '08:00:00', 'ends_at' => '11:00:00', 'classroom_id' => Classroom::factory()->create()->id]);

        // RM 1,000 billed, RM 600 paid: RM 400 owed by one student.
        $students[0]->charges()->create(['description' => 'Tuition fee', 'amount' => 1000]);
        $students[0]->payments()->create(['amount' => 600, 'paid_at' => now(), 'method' => 'Cash', 'receipt_no' => 'R-0001']);

        $this->actingAsRole('management');
        $response = $this->get('/statistics')->assertOk();

        $response->assertViewHas('register', fn ($r) => $r['count'] === 1 && $r['students'] === 1 && $r['marked'] === 0);
        $response->assertViewHas('results', fn ($r) => $r['marked'] === 2 && $r['passRate'] === 50.0);
        $response->assertViewHas('finance', fn ($f) => $f['billed'] === 1000.0 && $f['collected'] === 600.0
            && (float) $f['outstanding'] === 400.0 && $f['owing'] === 1 && $f['collectionRate'] === 60.0);
        $response->assertViewHas('timetable', fn ($t) => $t['sessions'] === 1 && $t['classes'] === 1 && $t['hours'] === 3.0);
        $response->assertSee('September 2026');
    }

    public function test_management_sees_everything_but_changes_nothing(): void
    {
        $this->actingAsRole('management');
        $student = Student::factory()->create();

        foreach (['/statistics', '/students', "/students/{$student->id}", '/finance', "/finance/students/{$student->id}",
            '/timetable', '/sessions', '/classrooms', '/subjects', '/programmes', '/departments', '/lecturers'] as $page) {
            $this->get($page)->assertOk();
        }

        $this->get("/finance/students/{$student->id}")->assertDontSee('Record payment');
        $this->post("/finance/students/{$student->id}/payments", ['amount' => 100])->assertForbidden();
        $this->get('/students/create')->assertForbidden();
        $this->get("/students/{$student->id}/edit")->assertForbidden();
        $this->post('/classrooms', [])->assertForbidden();
        $this->post('/sessions', [])->assertForbidden();
        $this->get('/users')->assertForbidden();
    }

    public function test_only_management_and_super_admin_see_statistics(): void
    {
        $this->actingAsRole('super_admin');
        $this->get('/statistics')->assertOk();

        foreach (['admin_staff', 'hod', 'registrar', 'lecturer', 'accountant', 'student'] as $role) {
            $this->actingAsRole($role);
            $this->get('/statistics')->assertForbidden();
        }
    }
}

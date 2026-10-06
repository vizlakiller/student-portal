<?php

namespace Tests\Feature;

use App\Models\ClassSession;
use App\Models\Classroom;
use App\Models\Result;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarksAndRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private Term $term;

    protected function setUp(): void
    {
        parent::setUp();
        $this->term = Term::factory()->current()->create();
    }

    private function sessionWithSlot(array $attributes = [], array $slot = [1, '08:00:00', '10:00:00']): ClassSession
    {
        $session = ClassSession::factory()->create(['term_id' => $this->term->id] + $attributes);
        $session->slots()->create(['day' => $slot[0], 'starts_at' => $slot[1], 'ends_at' => $slot[2], 'classroom_id' => Classroom::factory()->create()->id]);

        return $session;
    }

    public function test_registrar_registers_a_student_into_sessions(): void
    {
        $this->actingAsRole('registrar');
        $student = Student::factory()->create(['semester' => 2]);
        $a = $this->sessionWithSlot([], [1, '08:00:00', '10:00:00']);
        $b = $this->sessionWithSlot([], [2, '08:00:00', '10:00:00']);

        $this->get("/students/{$student->id}/registrations/create")->assertOk()->assertSee($a->subject->code)->assertSee($a->name);

        $this->post("/students/{$student->id}/registrations", [
            'sessions' => [$a->subject_id => $a->id, $b->subject_id => $b->id], 'semester' => 2,
        ])->assertRedirect(route('students.show', $student));

        $this->assertSame(2, $student->results()->whereNull('marks')->count());
        $this->assertSame($a->id, $student->results()->where('subject_id', $a->subject_id)->value('class_session_id'));
    }

    public function test_registration_refuses_timetable_clashes(): void
    {
        $this->actingAsRole('registrar');
        $student = Student::factory()->create();
        $a = $this->sessionWithSlot([], [1, '08:00:00', '10:00:00']);
        $b = $this->sessionWithSlot([], [1, '09:00:00', '11:00:00']);   // overlaps Monday 09:00

        $this->post("/students/{$student->id}/registrations", [
            'sessions' => [$a->subject_id => $a->id, $b->subject_id => $b->id], 'semester' => 1,
        ])->assertSessionHasErrors('sessions');

        $this->assertSame(0, $student->results()->count());
    }

    public function test_registration_refuses_clash_with_existing_classes_and_full_sessions(): void
    {
        $this->actingAsRole('registrar');
        $student = Student::factory()->create();
        $existing = $this->sessionWithSlot([], [3, '10:00:00', '12:00:00']);
        $student->results()->create(['subject_id' => $existing->subject_id, 'class_session_id' => $existing->id, 'semester' => 1]);

        $clashing = $this->sessionWithSlot([], [3, '11:00:00', '13:00:00']);
        $this->post("/students/{$student->id}/registrations", ['sessions' => [$clashing->subject_id => $clashing->id], 'semester' => 1])
            ->assertSessionHasErrors('sessions');

        $full = $this->sessionWithSlot(['capacity' => 1], [4, '08:00:00', '10:00:00']);
        Result::factory()->for($full->subject)->create(['class_session_id' => $full->id, 'marks' => null]);
        $this->post("/students/{$student->id}/registrations", ['sessions' => [$full->subject_id => $full->id], 'semester' => 1])
            ->assertSessionHasErrors('sessions');
    }

    public function test_sessions_from_another_term_cannot_be_used(): void
    {
        $this->actingAsRole('registrar');
        $student = Student::factory()->create();
        $old = ClassSession::factory()->create(['term_id' => Term::factory()->create()->id]);

        $this->post("/students/{$student->id}/registrations", ['sessions' => [$old->subject_id => $old->id], 'semester' => 1])
            ->assertSessionHasErrors('sessions');
    }

    public function test_registrar_can_remove_an_unmarked_subject_but_not_a_marked_one(): void
    {
        $this->actingAsRole('registrar');
        $unmarked = Result::factory()->create(['marks' => null]);
        $marked = Result::factory()->create(['marks' => 60]);

        $this->delete("/students/{$unmarked->student_id}/results/{$unmarked->id}")->assertSessionHas('success');
        $this->assertDatabaseMissing('results', ['id' => $unmarked->id]);

        $this->delete("/students/{$marked->student_id}/results/{$marked->id}")->assertSessionHas('error');
        $this->assertDatabaseHas('results', ['id' => $marked->id]);
    }

    public function test_lecturer_enters_marks_only_for_their_own_session(): void
    {
        $lecturer = $this->actingAsRole('lecturer');
        $mine = ClassSession::factory()->create(['term_id' => $this->term->id]);
        $mine->lecturers()->attach($lecturer);
        $first = Result::factory()->for($mine->subject)->create(['marks' => null, 'class_session_id' => $mine->id]);
        $second = Result::factory()->for($mine->subject)->create(['marks' => null, 'class_session_id' => $mine->id]);

        // Another group of the same subject, taught by someone else
        $other = ClassSession::factory()->create(['term_id' => $this->term->id, 'subject_id' => $mine->subject_id]);
        $notMine = Result::factory()->for($mine->subject)->create(['marks' => 40, 'class_session_id' => $other->id]);

        $this->get('/my-sessions')->assertOk()->assertSee($mine->name)->assertDontSee($other->name);
        $this->get("/sessions/{$mine->id}/marks")->assertOk()->assertSee($first->student->name)->assertDontSee($notMine->student->name);

        $this->put("/sessions/{$mine->id}/marks", ['marks' => [$first->id => 77, $second->id => null, $notMine->id => 99]])
            ->assertRedirect(route('marks.edit', $mine));

        $this->assertSame('A-', $first->fresh()->grade);
        $this->assertNull($second->fresh()->marks);
        $this->assertSame(40, $notMine->fresh()->marks);   // other session's marks ignored

        $this->put("/sessions/{$other->id}/marks", ['marks' => [$notMine->id => 99]])->assertForbidden();
    }

    public function test_marks_must_be_between_0_and_100(): void
    {
        $lecturer = $this->actingAsRole('lecturer');
        $session = ClassSession::factory()->create(['term_id' => $this->term->id]);
        $session->lecturers()->attach($lecturer);
        $result = Result::factory()->for($session->subject)->create(['marks' => null, 'class_session_id' => $session->id]);

        $this->put("/sessions/{$session->id}/marks", ['marks' => [$result->id => 101]])->assertSessionHasErrors("marks.{$result->id}");
        $this->assertNull($result->fresh()->marks);
    }

    public function test_co_lecturers_of_a_session_can_both_enter_marks(): void
    {
        $session = ClassSession::factory()->create(['term_id' => $this->term->id]);
        [$one, $two] = User::factory()->role('lecturer')->count(2)->create();
        $session->lecturers()->attach([$one->id, $two->id]);

        $this->actingAs($one)->get("/sessions/{$session->id}/marks")->assertOk();
        $this->actingAs($two)->get("/sessions/{$session->id}/marks")->assertOk();
    }

    public function test_super_admin_can_change_marks_directly(): void
    {
        $this->actingAsRole('super_admin');
        $result = Result::factory()->create(['marks' => 40]);

        $this->put("/students/{$result->student_id}/results/{$result->id}", ['semester' => 2, 'marks' => 90])->assertSessionHasNoErrors();
        $this->assertSame(90, $result->fresh()->marks);
    }

    public function test_a_result_cannot_be_reached_through_another_student(): void
    {
        $this->actingAsRole('super_admin');
        $result = Result::factory()->create();
        $other = Student::factory()->create();

        $this->put("/students/{$other->id}/results/{$result->id}", ['semester' => 1, 'marks' => 100])->assertNotFound();
    }
}

<?php

namespace Tests\Feature;

use App\Models\Result;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarksAndRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registrar_registers_a_student_for_several_subjects_without_marks(): void
    {
        $this->actingAsRole('registrar');
        $student = Student::factory()->create(['semester' => 2]);
        [$a, $b] = Subject::factory()->count(2)->create();

        $this->get("/students/{$student->id}/registrations/create")->assertOk()->assertSee($a->code);
        $this->post("/students/{$student->id}/registrations", ['subject_ids' => [$a->id, $b->id], 'semester' => 2])
            ->assertRedirect(route('students.show', $student));

        $this->assertSame(2, $student->results()->whereNull('marks')->where('semester', 2)->count());
    }

    public function test_a_subject_cannot_be_registered_twice(): void
    {
        $this->actingAsRole('registrar');
        $result = Result::factory()->create(['marks' => null]);

        $this->post("/students/{$result->student_id}/registrations", ['subject_ids' => [$result->subject_id], 'semester' => 1])
            ->assertSessionHasErrors('subject_ids.0');
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

    public function test_teacher_sees_their_subjects_and_enters_marks(): void
    {
        $teacher = $this->actingAsRole('teacher');
        $subject = Subject::factory()->create(['teacher_id' => $teacher->id]);
        $first = Result::factory()->for($subject)->create(['marks' => null]);
        $second = Result::factory()->for($subject)->create(['marks' => null]);

        $this->get('/my-subjects')->assertOk()->assertSee($subject->code);
        $this->get("/subjects/{$subject->id}/marks")->assertOk()->assertSee($first->student->name);

        $this->put("/subjects/{$subject->id}/marks", ['marks' => [$first->id => 77, $second->id => null]])
            ->assertRedirect(route('marks.edit', $subject));

        $this->assertSame(77, $first->fresh()->marks);
        $this->assertSame('A-', $first->fresh()->grade);
        $this->assertNull($second->fresh()->marks);
    }

    public function test_marks_must_be_between_0_and_100(): void
    {
        $teacher = $this->actingAsRole('teacher');
        $subject = Subject::factory()->create(['teacher_id' => $teacher->id]);
        $result = Result::factory()->for($subject)->create(['marks' => null]);

        $this->put("/subjects/{$subject->id}/marks", ['marks' => [$result->id => 101]])->assertSessionHasErrors("marks.{$result->id}");
        $this->assertNull($result->fresh()->marks);
    }

    public function test_teacher_cannot_save_marks_for_another_teachers_subject(): void
    {
        $other = User::factory()->role('teacher')->create();
        $subject = Subject::factory()->create(['teacher_id' => $other->id]);
        $result = Result::factory()->for($subject)->create(['marks' => null]);

        $this->actingAsRole('teacher');
        $this->put("/subjects/{$subject->id}/marks", ['marks' => [$result->id => 90]])->assertForbidden();
        $this->assertNull($result->fresh()->marks);
    }

    public function test_marks_for_results_of_other_subjects_are_ignored(): void
    {
        $teacher = $this->actingAsRole('teacher');
        $mine = Subject::factory()->create(['teacher_id' => $teacher->id]);
        $notMine = Result::factory()->create(['marks' => 40]);

        $this->put("/subjects/{$mine->id}/marks", ['marks' => [$notMine->id => 99]]);
        $this->assertSame(40, $notMine->fresh()->marks);
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

<?php

namespace Tests\Feature;

use App\Models\ClassSession;
use App\Models\Department;
use App\Models\Result;
use App\Models\ResultChangeRequest;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A head of department asks for a mark change; it applies only when a
 * lecturer of the student's session approves it.
 */
class MarkChangeTest extends TestCase
{
    use RefreshDatabase;

    private User $hod;

    private User $lecturer;

    private Subject $subject;

    private Result $result;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hod = User::factory()->role('hod')->create();
        $department = Department::factory()->create(['hod_id' => $this->hod->id]);

        $this->lecturer = User::factory()->role('lecturer')->create();
        $this->subject = Subject::factory()->create(['department_id' => $department->id]);
        $this->subject->lecturers()->attach($this->lecturer);

        $session = ClassSession::factory()->create(['term_id' => Term::factory()->current()->create()->id, 'subject_id' => $this->subject->id]);
        $session->lecturers()->attach($this->lecturer);

        $this->result = Result::factory()->for($this->subject)->create(['class_session_id' => $session->id, 'marks' => 60]);
    }

    private function hodRequests(int $newMarks = 70): ResultChangeRequest
    {
        $this->actingAs($this->hod);
        $this->post("/results/{$this->result->id}/mark-change", ['new_marks' => $newMarks, 'reason' => 'Re-marked after appeal'])
            ->assertRedirect(route('students.show', $this->result->student_id))
            ->assertSessionHas('success', fn ($message) => str_contains($message, $this->lecturer->name));

        return ResultChangeRequest::firstOrFail();
    }

    public function test_hod_request_does_not_change_the_mark_until_the_lecturer_approves(): void
    {
        $request = $this->hodRequests(70);
        $this->assertSame(60, $this->result->fresh()->marks);
        $this->assertTrue($request->isPending());

        $this->actingAs($this->lecturer);
        $this->get('/dashboard')->assertSee('Waiting for your approval');
        $this->post("/mark-changes/{$request->id}/approve")->assertSessionHas('success');

        $this->assertSame(70, $this->result->fresh()->marks);
        $this->assertSame('approved', $request->fresh()->status);
        $this->assertSame($this->lecturer->id, $request->fresh()->decided_by);
    }

    public function test_lecturer_can_reject_and_the_mark_stays(): void
    {
        $request = $this->hodRequests(70);

        $this->actingAs($this->lecturer);
        $this->post("/mark-changes/{$request->id}/reject", ['decision_note' => 'Marks were correct'])->assertSessionHas('success');

        $this->assertSame(60, $this->result->fresh()->marks);
        $this->assertSame('rejected', $request->fresh()->status);
        $this->assertSame('Marks were correct', $request->fresh()->decision_note);
    }

    public function test_only_a_lecturer_of_the_students_session_can_decide(): void
    {
        $request = $this->hodRequests();

        // Teaches the same subject, but another group: not their student.
        $otherLecturer = User::factory()->role('lecturer')->create();
        $this->subject->lecturers()->attach($otherLecturer);
        $this->actingAs($otherLecturer);
        $this->post("/mark-changes/{$request->id}/approve")->assertForbidden();

        $this->actingAs($this->hod);                    // the HOD can't approve their own request
        $this->post("/mark-changes/{$request->id}/approve")->assertForbidden();

        $this->assertSame(60, $this->result->fresh()->marks);
    }

    public function test_results_without_a_session_are_decided_by_the_subject_lecturers(): void
    {
        $old = Result::factory()->for($this->subject)->create(['marks' => 55]);   // from before sessions existed

        $this->actingAs($this->hod);
        $this->post("/results/{$old->id}/mark-change", ['new_marks' => 58, 'reason' => 'Added up wrongly']);
        $request = $old->changeRequests()->firstOrFail();

        $this->actingAs($this->lecturer);
        $this->post("/mark-changes/{$request->id}/approve")->assertSessionHas('success');
        $this->assertSame(58, $old->fresh()->marks);
    }

    public function test_super_admin_can_decide_but_a_decided_request_is_final(): void
    {
        $request = $this->hodRequests();

        $this->actingAsRole('super_admin');
        $this->post("/mark-changes/{$request->id}/approve")->assertSessionHas('success');
        $this->assertSame(70, $this->result->fresh()->marks);

        // Nobody can decide it again, not even the super admin.
        $this->post("/mark-changes/{$request->id}/reject")->assertForbidden();
        $this->actingAs($this->lecturer)->post("/mark-changes/{$request->id}/reject")->assertForbidden();
        $this->assertSame('approved', $request->fresh()->status);
    }

    public function test_only_one_pending_request_per_result_and_new_marks_must_differ(): void
    {
        $this->hodRequests();

        $this->post("/results/{$this->result->id}/mark-change", ['new_marks' => 80, 'reason' => 'Again'])->assertForbidden();

        $other = Result::factory()->for($this->subject)->create(['marks' => 50]);
        $this->post("/results/{$other->id}/mark-change", ['new_marks' => 50, 'reason' => 'Same'])->assertSessionHasErrors('new_marks');
    }

    public function test_hod_can_only_request_changes_in_their_own_departments(): void
    {
        $otherDepartmentResult = Result::factory()->create([
            'subject_id' => Subject::factory()->create(['department_id' => Department::factory()->create()->id])->id,
            'marks' => 40,
        ]);

        $this->actingAs($this->hod);
        $this->get("/results/{$otherDepartmentResult->id}/mark-change")->assertForbidden();
        $this->post("/results/{$otherDepartmentResult->id}/mark-change", ['new_marks' => 45, 'reason' => 'x'])->assertForbidden();

        // They can still see that student's results.
        $this->get("/students/{$otherDepartmentResult->student_id}")->assertOk()->assertSee($otherDepartmentResult->subject->code)
            ->assertDontSee(route('mark-changes.create', $otherDepartmentResult));
        $this->get("/students/{$this->result->student_id}")->assertSee(route('mark-changes.create', $this->result));
    }

    public function test_registrar_and_lecturer_cannot_request_changes(): void
    {
        $this->actingAsRole('registrar');
        $this->post("/results/{$this->result->id}/mark-change", ['new_marks' => 90, 'reason' => 'x'])->assertForbidden();

        $this->actingAs($this->lecturer);
        $this->post("/results/{$this->result->id}/mark-change", ['new_marks' => 90, 'reason' => 'x'])->assertForbidden();
    }

    public function test_requests_list_shows_each_person_the_right_requests(): void
    {
        $this->hodRequests();
        $this->get('/mark-changes')->assertOk()->assertSee('Re-marked after appeal');

        $this->actingAs($this->lecturer);
        $this->get('/mark-changes')->assertOk()->assertSee('Re-marked after appeal')->assertSee('Approve');

        $this->actingAsRole('lecturer');
        $this->get('/mark-changes')->assertOk()->assertDontSee('Re-marked after appeal');

        $this->actingAsHod();                            // another head of department
        $this->get('/mark-changes')->assertOk()->assertDontSee('Re-marked after appeal');
    }
}

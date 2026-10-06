<?php

namespace Tests\Feature;

use App\Models\Result;
use App\Models\ResultChangeRequest;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Head of department asks for a mark change; it applies only when the subject teacher approves.
 */
class MarkChangeTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private Result $result;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teacher = User::factory()->role('teacher')->create();
        $subject = Subject::factory()->create(['teacher_id' => $this->teacher->id]);
        $this->result = Result::factory()->for($subject)->create(['marks' => 60]);
    }

    private function hodRequests(int $newMarks = 70): ResultChangeRequest
    {
        $this->actingAsRole('hod');
        $this->post("/results/{$this->result->id}/mark-change", ['new_marks' => $newMarks, 'reason' => 'Re-marked after appeal'])
            ->assertRedirect(route('students.show', $this->result->student_id));

        return ResultChangeRequest::firstOrFail();
    }

    public function test_hod_request_does_not_change_the_mark_until_the_teacher_approves(): void
    {
        $request = $this->hodRequests(70);
        $this->assertSame(60, $this->result->fresh()->marks);
        $this->assertTrue($request->isPending());

        $this->actingAs($this->teacher);
        $this->get('/dashboard')->assertSee('Waiting for your approval');
        $this->post("/mark-changes/{$request->id}/approve")->assertSessionHas('success');

        $this->assertSame(70, $this->result->fresh()->marks);
        $this->assertSame('approved', $request->fresh()->status);
        $this->assertSame($this->teacher->id, $request->fresh()->decided_by);
    }

    public function test_teacher_can_reject_and_the_mark_stays(): void
    {
        $request = $this->hodRequests(70);

        $this->actingAs($this->teacher);
        $this->post("/mark-changes/{$request->id}/reject", ['decision_note' => 'Marks were correct'])->assertSessionHas('success');

        $this->assertSame(60, $this->result->fresh()->marks);
        $this->assertSame('rejected', $request->fresh()->status);
        $this->assertSame('Marks were correct', $request->fresh()->decision_note);
    }

    public function test_only_the_subject_teacher_can_decide(): void
    {
        $request = $this->hodRequests();

        $this->actingAsRole('teacher');               // a different teacher
        $this->post("/mark-changes/{$request->id}/approve")->assertForbidden();

        $this->actingAsRole('hod');                   // the HOD can't approve their own request
        $this->post("/mark-changes/{$request->id}/approve")->assertForbidden();

        $this->assertSame(60, $this->result->fresh()->marks);
    }

    public function test_a_decided_request_cannot_be_decided_again(): void
    {
        $request = $this->hodRequests();
        $this->actingAs($this->teacher)->post("/mark-changes/{$request->id}/reject");

        $this->post("/mark-changes/{$request->id}/approve")->assertForbidden();
        $this->assertSame(60, $this->result->fresh()->marks);
    }

    public function test_only_one_pending_request_per_result_and_new_marks_must_differ(): void
    {
        $this->hodRequests();

        $this->post("/results/{$this->result->id}/mark-change", ['new_marks' => 80, 'reason' => 'Again'])->assertForbidden();

        $other = Result::factory()->create(['marks' => 50]);
        $this->post("/results/{$other->id}/mark-change", ['new_marks' => 50, 'reason' => 'Same'])->assertSessionHasErrors('new_marks');
    }

    public function test_registrar_and_teacher_cannot_request_changes(): void
    {
        $this->actingAsRole('registrar');
        $this->post("/results/{$this->result->id}/mark-change", ['new_marks' => 90, 'reason' => 'x'])->assertForbidden();

        $this->actingAs($this->teacher);
        $this->post("/results/{$this->result->id}/mark-change", ['new_marks' => 90, 'reason' => 'x'])->assertForbidden();
    }

    public function test_requests_list_shows_each_person_the_right_requests(): void
    {
        $request = $this->hodRequests();
        $this->get('/mark-changes')->assertOk()->assertSee('Re-marked after appeal');

        $this->actingAs($this->teacher);
        $this->get('/mark-changes')->assertOk()->assertSee('Re-marked after appeal')->assertSee('Approve');

        $this->actingAsRole('teacher');
        $this->get('/mark-changes')->assertOk()->assertDontSee('Re-marked after appeal');
    }
}

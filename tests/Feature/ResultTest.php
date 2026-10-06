<?php

namespace Tests\Feature;

use App\Models\Result;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResultTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_a_result_can_be_added_and_its_grade_is_calculated(): void
    {
        $student = Student::factory()->create();
        $subject = Subject::factory()->create();

        $this->get("/students/{$student->id}/results/create")->assertOk()->assertSee($subject->code);

        $this->post("/students/{$student->id}/results", [
            'subject_id' => $subject->id, 'semester' => 1, 'marks' => 77,
        ])->assertRedirect(route('students.show', $student))
          ->assertSessionHas('success', "Result saved: {$subject->code}, grade A-.");

        $result = $student->results()->firstOrFail();
        $this->assertSame('A-', $result->grade);
        $this->assertSame(3.67, $result->grade_point);
    }

    public function test_same_subject_cannot_be_added_twice_for_a_student(): void
    {
        $result = Result::factory()->create();

        $this->post("/students/{$result->student_id}/results", [
            'subject_id' => $result->subject_id, 'semester' => 2, 'marks' => 60,
        ])->assertSessionHasErrors('subject_id');
    }

    public function test_marks_must_be_between_0_and_100(): void
    {
        $student = Student::factory()->create();
        $subject = Subject::factory()->create();

        $this->post("/students/{$student->id}/results", ['subject_id' => $subject->id, 'semester' => 1, 'marks' => 101])
            ->assertSessionHasErrors('marks');
        $this->post("/students/{$student->id}/results", ['subject_id' => $subject->id, 'semester' => 1, 'marks' => -1])
            ->assertSessionHasErrors('marks');
    }

    public function test_a_result_can_be_updated_and_deleted(): void
    {
        $result = Result::factory()->create(['marks' => 40]);
        $url = "/students/{$result->student_id}/results/{$result->id}";

        $this->get("{$url}/edit")->assertOk();
        $this->put($url, ['semester' => 2, 'marks' => 90])->assertSessionHasNoErrors();
        $this->assertSame(90, $result->fresh()->marks);

        $this->delete($url)->assertRedirect(route('students.show', $result->student_id));
        $this->assertDatabaseMissing('results', ['id' => $result->id]);
    }

    public function test_a_result_cannot_be_changed_through_another_student(): void
    {
        $result = Result::factory()->create();
        $otherStudent = Student::factory()->create();

        $this->put("/students/{$otherStudent->id}/results/{$result->id}", ['semester' => 1, 'marks' => 100])
            ->assertNotFound();
    }

    public function test_add_result_redirects_when_every_subject_is_taken(): void
    {
        $result = Result::factory()->create();   // the only subject

        $this->get("/students/{$result->student_id}/results/create")
            ->assertRedirect(route('students.show', $result->student_id))
            ->assertSessionHas('error');
    }
}

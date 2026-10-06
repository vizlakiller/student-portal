<?php

namespace Tests\Feature;

use App\Models\Programme;
use App\Models\Result;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgrammeAndSubjectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_programmes_can_be_added_edited_and_deleted(): void
    {
        $this->get('/programmes/create')->assertOk();
        $this->post('/programmes', ['code' => 'DIT', 'name' => 'Diploma in IT', 'level' => 'Diploma'])
            ->assertRedirect('/programmes');
        $programme = Programme::where('code', 'DIT')->firstOrFail();

        $this->get('/programmes')->assertOk()->assertSee('Diploma in IT');
        $this->put("/programmes/{$programme->id}", ['code' => 'DIT', 'name' => 'Diploma in Information Technology', 'level' => 'Diploma'])
            ->assertRedirect('/programmes');
        $this->assertSame('Diploma in Information Technology', $programme->fresh()->name);

        $this->delete("/programmes/{$programme->id}")->assertRedirect('/programmes');
        $this->assertDatabaseMissing('programmes', ['id' => $programme->id]);
    }

    public function test_programme_with_students_cannot_be_deleted(): void
    {
        $student = Student::factory()->create();

        $this->delete("/programmes/{$student->programme_id}")->assertSessionHas('error');
        $this->assertDatabaseHas('programmes', ['id' => $student->programme_id]);
    }

    public function test_programme_code_must_be_unique_and_level_valid(): void
    {
        Programme::factory()->create(['code' => 'DCS']);

        $this->post('/programmes', ['code' => 'DCS', 'name' => 'Duplicate', 'level' => 'Masters'])
            ->assertSessionHasErrors(['code', 'level']);
    }

    public function test_subjects_can_be_added_edited_and_deleted(): void
    {
        $this->post('/subjects', ['code' => 'WEB2013', 'name' => 'Web Development', 'credit_hours' => 3])
            ->assertRedirect('/subjects');
        $subject = Subject::where('code', 'WEB2013')->firstOrFail();

        $this->get('/subjects')->assertOk()->assertSee('Web Development');
        $this->put("/subjects/{$subject->id}", ['code' => 'WEB2013', 'name' => 'Web Application Development', 'credit_hours' => 4])
            ->assertRedirect('/subjects');
        $this->assertSame(4, $subject->fresh()->credit_hours);

        $this->delete("/subjects/{$subject->id}")->assertRedirect('/subjects');
        $this->assertDatabaseMissing('subjects', ['id' => $subject->id]);
    }

    public function test_subject_with_results_cannot_be_deleted(): void
    {
        $result = Result::factory()->create();

        $this->delete("/subjects/{$result->subject_id}")->assertSessionHas('error');
        $this->assertDatabaseHas('subjects', ['id' => $result->subject_id]);
    }

    public function test_credit_hours_must_be_between_1_and_6(): void
    {
        $this->post('/subjects', ['code' => 'X1', 'name' => 'Too heavy', 'credit_hours' => 9])
            ->assertSessionHasErrors('credit_hours');
    }
}

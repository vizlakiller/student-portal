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

    public function test_registrar_adds_edits_and_deletes_programmes(): void
    {
        $this->actingAsRole('registrar');

        $this->post('/programmes', ['code' => 'DIT', 'name' => 'Diploma in IT', 'level' => 'Diploma'])->assertRedirect('/programmes');
        $programme = Programme::where('code', 'DIT')->firstOrFail();

        $this->put("/programmes/{$programme->id}", ['code' => 'DIT', 'name' => 'Diploma in Information Technology', 'level' => 'Diploma'])
            ->assertRedirect('/programmes');
        $this->assertSame('Diploma in Information Technology', $programme->fresh()->name);

        $this->delete("/programmes/{$programme->id}")->assertRedirect('/programmes');
        $this->assertDatabaseMissing('programmes', ['id' => $programme->id]);
    }

    public function test_programme_with_students_cannot_be_deleted(): void
    {
        $this->actingAsRole('registrar');
        $student = Student::factory()->create();

        $this->delete("/programmes/{$student->programme_id}")->assertSessionHas('error');
        $this->assertDatabaseHas('programmes', ['id' => $student->programme_id]);
    }

    public function test_programme_code_must_be_unique_and_level_valid(): void
    {
        $this->actingAsRole('registrar');
        Programme::factory()->create(['code' => 'DCS']);

        $this->post('/programmes', ['code' => 'DCS', 'name' => 'Duplicate', 'level' => 'Masters'])->assertSessionHasErrors(['code', 'level']);
    }

    public function test_hod_adds_subjects_and_assigns_a_teacher(): void
    {
        $this->actingAsRole('hod');
        $teacher = User::factory()->role('teacher')->create();

        $this->post('/subjects', ['code' => 'WEB2013', 'name' => 'Web Development', 'credit_hours' => 3, 'teacher_id' => $teacher->id])
            ->assertRedirect('/subjects');

        $subject = Subject::where('code', 'WEB2013')->firstOrFail();
        $this->assertSame($teacher->id, $subject->teacher_id);
        $this->get('/subjects')->assertSee($teacher->name);
    }

    public function test_only_teacher_accounts_can_be_assigned(): void
    {
        $this->actingAsRole('hod');
        $accountant = User::factory()->role('accountant')->create();
        $subject = Subject::factory()->create();

        $this->put("/subjects/{$subject->id}", ['code' => $subject->code, 'name' => $subject->name, 'credit_hours' => 3, 'teacher_id' => $accountant->id])
            ->assertSessionHasErrors('teacher_id');
    }

    public function test_subject_with_students_cannot_be_deleted_and_credits_are_checked(): void
    {
        $this->actingAsRole('hod');
        $result = Result::factory()->create();

        $this->delete("/subjects/{$result->subject_id}")->assertSessionHas('error');
        $this->assertDatabaseHas('subjects', ['id' => $result->subject_id]);

        $this->post('/subjects', ['code' => 'X1', 'name' => 'Too heavy', 'credit_hours' => 9])->assertSessionHasErrors('credit_hours');
    }

    public function test_admin_staff_and_registrar_can_only_view_subjects(): void
    {
        $this->actingAsRole('registrar');
        $this->get('/subjects')->assertOk()->assertDontSee('Add subject');
        $this->post('/subjects', ['code' => 'X1', 'name' => 'X', 'credit_hours' => 3])->assertForbidden();
    }
}

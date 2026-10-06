<?php

namespace Tests\Feature;

use App\Models\ClassSession;
use App\Models\Department;
use App\Models\Programme;
use App\Models\Result;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgrammeAndSubjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_registrar_adds_edits_and_deletes_programmes(): void
    {
        $this->actingAsRole('registrar');
        $department = Department::factory()->create();

        $this->post('/programmes', ['code' => 'DIT', 'name' => 'Diploma in IT', 'level' => 'Diploma', 'department_id' => $department->id])
            ->assertRedirect('/programmes');
        $programme = Programme::where('code', 'DIT')->firstOrFail();
        $this->assertSame($department->id, $programme->department_id);

        $this->put("/programmes/{$programme->id}", ['code' => 'DIT', 'name' => 'Diploma in Information Technology', 'level' => 'Diploma', 'department_id' => ''])
            ->assertRedirect('/programmes');
        $this->assertSame('Diploma in Information Technology', $programme->fresh()->name);
        $this->assertNull($programme->fresh()->department_id);

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

        $this->post('/programmes', ['code' => 'DCS', 'name' => 'Duplicate', 'level' => 'Masters', 'department_id' => 999])
            ->assertSessionHasErrors(['code', 'level', 'department_id']);
    }

    public function test_hod_adds_a_subject_to_their_department_with_several_lecturers(): void
    {
        [, $department] = $this->actingAsHod();
        $lecturers = User::factory()->role('lecturer')->count(2)->create();

        $this->post('/subjects', [
            'code' => 'WEB2013', 'name' => 'Web Development', 'credit_hours' => 3,
            'department_id' => $department->id, 'lecturer_ids' => $lecturers->pluck('id')->all(),
        ])->assertRedirect('/subjects');

        $subject = Subject::where('code', 'WEB2013')->firstOrFail();
        $this->assertSame($department->id, $subject->department_id);
        $this->assertEqualsCanonicalizing($lecturers->pluck('id')->all(), $subject->lecturers->pluck('id')->all());
        $this->get('/subjects')->assertSee($lecturers[0]->name)->assertSee($lecturers[1]->name);
    }

    public function test_hod_cannot_use_or_manage_another_departments_subjects(): void
    {
        $this->actingAsHod();
        $otherDepartment = Department::factory()->create();
        $otherSubject = Subject::factory()->create(['department_id' => $otherDepartment->id]);

        $this->post('/subjects', ['code' => 'X1', 'name' => 'X', 'credit_hours' => 3, 'department_id' => $otherDepartment->id])
            ->assertSessionHasErrors('department_id');
        $this->post('/subjects', ['code' => 'X1', 'name' => 'X', 'credit_hours' => 3])->assertSessionHasErrors('department_id');

        $this->get("/subjects/{$otherSubject->id}/edit")->assertForbidden();
        $this->put("/subjects/{$otherSubject->id}", ['code' => 'X2', 'name' => 'X', 'credit_hours' => 3, 'department_id' => $otherDepartment->id])->assertForbidden();
        $this->delete("/subjects/{$otherSubject->id}")->assertForbidden();
        $this->assertDatabaseHas('subjects', ['id' => $otherSubject->id, 'code' => $otherSubject->code]);

        // Still listed for viewing, without an Edit link.
        $this->get('/subjects')->assertOk()->assertSee($otherSubject->code)->assertDontSee(route('subjects.edit', $otherSubject));
    }

    public function test_only_lecturer_accounts_can_be_assigned(): void
    {
        [, $department] = $this->actingAsHod();
        $accountant = User::factory()->role('accountant')->create();
        $subject = Subject::factory()->create(['department_id' => $department->id]);

        $this->put("/subjects/{$subject->id}", [
            'code' => $subject->code, 'name' => $subject->name, 'credit_hours' => 3,
            'department_id' => $department->id, 'lecturer_ids' => [$accountant->id],
        ])->assertSessionHasErrors('lecturer_ids.0');
    }

    public function test_lecturer_taken_off_a_subject_leaves_its_current_sessions(): void
    {
        [, $department] = $this->actingAsHod();
        [$staying, $leaving] = User::factory()->role('lecturer')->count(2)->create();
        $subject = Subject::factory()->create(['department_id' => $department->id]);
        $subject->lecturers()->attach([$staying->id, $leaving->id]);

        $session = ClassSession::factory()->create(['term_id' => Term::factory()->current()->create()->id, 'subject_id' => $subject->id]);
        $session->lecturers()->attach([$staying->id, $leaving->id]);

        $this->put("/subjects/{$subject->id}", [
            'code' => $subject->code, 'name' => $subject->name, 'credit_hours' => 3,
            'department_id' => $department->id, 'lecturer_ids' => [$staying->id],
        ])->assertRedirect('/subjects');

        $this->assertSame([$staying->id], $session->lecturers()->pluck('users.id')->all());
    }

    public function test_subject_with_students_or_sessions_cannot_be_deleted_and_credits_are_checked(): void
    {
        [, $department] = $this->actingAsHod();
        $withStudents = Result::factory()->for(Subject::factory()->state(['department_id' => $department->id]))->create()->subject;
        $withSession = ClassSession::factory()->create([
            'term_id' => Term::factory()->create()->id,
            'subject_id' => Subject::factory()->create(['department_id' => $department->id])->id,
        ])->subject;

        $this->delete("/subjects/{$withStudents->id}")->assertSessionHas('error');
        $this->delete("/subjects/{$withSession->id}")->assertSessionHas('error');
        $this->assertSame(2, Subject::count());

        $this->post('/subjects', ['code' => 'X1', 'name' => 'Too heavy', 'credit_hours' => 9, 'department_id' => $department->id])
            ->assertSessionHasErrors('credit_hours');
    }

    public function test_super_admin_can_manage_any_subject_and_leave_department_empty(): void
    {
        $this->actingAsRole('super_admin');
        $subject = Subject::factory()->create(['department_id' => Department::factory()->create()->id]);

        $this->put("/subjects/{$subject->id}", ['code' => $subject->code, 'name' => 'Renamed', 'credit_hours' => 2, 'department_id' => ''])
            ->assertRedirect('/subjects');
        $this->assertSame('Renamed', $subject->fresh()->name);
        $this->assertNull($subject->fresh()->department_id);
    }

    public function test_registrar_and_management_can_only_view_subjects(): void
    {
        foreach (['registrar', 'management', 'admin_staff'] as $role) {
            $this->actingAsRole($role);
            $this->get('/subjects')->assertOk()->assertDontSee('Add subject');
            $this->post('/subjects', ['code' => 'X1', 'name' => 'X', 'credit_hours' => 3])->assertForbidden();
        }
    }
}

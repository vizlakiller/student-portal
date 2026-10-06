<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Programme;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Departments: one head each; one head may lead several departments.
 */
class DepartmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_adds_departments_and_one_hod_can_head_several(): void
    {
        $this->actingAsRole('super_admin');
        $hod = User::factory()->role('hod')->create(['name' => 'Dr. Rahim']);

        $this->post('/departments', ['code' => 'DCOMP', 'name' => 'Department of Computing', 'hod_id' => $hod->id])->assertRedirect('/departments');
        $this->post('/departments', ['code' => 'DGEN', 'name' => 'Centre for General Studies', 'hod_id' => $hod->id])->assertRedirect('/departments');

        $this->assertSame(2, $hod->departments()->count());
        $this->get('/departments')->assertOk()->assertSee('DCOMP')->assertSee('DGEN')->assertSee('Dr. Rahim');
    }

    public function test_department_head_must_be_a_hod_account_and_code_unique(): void
    {
        $this->actingAsRole('super_admin');
        $lecturer = User::factory()->role('lecturer')->create();
        Department::factory()->create(['code' => 'DCOMP']);

        $this->post('/departments', ['code' => 'DCOMP', 'name' => 'Again', 'hod_id' => $lecturer->id])
            ->assertSessionHasErrors(['code', 'hod_id']);
    }

    public function test_changing_the_head_moves_the_department(): void
    {
        $this->actingAsRole('super_admin');
        [$first, $second] = User::factory()->role('hod')->count(2)->create();
        $department = Department::factory()->create(['hod_id' => $first->id]);

        $this->put("/departments/{$department->id}", ['code' => $department->code, 'name' => $department->name, 'hod_id' => $second->id])
            ->assertRedirect('/departments');

        $this->assertSame($second->id, $department->fresh()->hod_id);
        $this->assertSame(0, $first->departments()->count());
    }

    public function test_department_with_programmes_or_subjects_cannot_be_deleted(): void
    {
        $this->actingAsRole('super_admin');
        $withProgramme = Department::factory()->create();
        Programme::factory()->create(['department_id' => $withProgramme->id]);
        $withSubject = Department::factory()->create();
        Subject::factory()->create(['department_id' => $withSubject->id]);
        $empty = Department::factory()->create();

        $this->delete("/departments/{$withProgramme->id}")->assertSessionHas('error');
        $this->delete("/departments/{$withSubject->id}")->assertSessionHas('error');
        $this->delete("/departments/{$empty->id}")->assertRedirect('/departments');

        $this->assertSame(2, Department::count());
    }

    public function test_other_staff_can_only_view_departments(): void
    {
        $department = Department::factory()->create();

        foreach (['hod', 'registrar', 'admin_staff', 'management'] as $role) {
            $this->actingAsRole($role);
            $this->get('/departments')->assertOk()->assertSee($department->code)->assertDontSee('Add department');
            $this->post('/departments', ['code' => 'X', 'name' => 'X'])->assertForbidden();
            $this->delete("/departments/{$department->id}")->assertForbidden();
        }

        foreach (['lecturer', 'accountant', 'student'] as $role) {
            $this->actingAsRole($role);
            $this->get('/departments')->assertForbidden();
        }
    }
}

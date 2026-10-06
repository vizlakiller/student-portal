<?php

namespace Tests\Feature;

use App\Models\ClassSession;
use App\Models\Department;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_creates_staff_accounts_with_a_role(): void
    {
        $this->actingAsRole('super_admin');

        $this->post('/users', [
            'name' => 'Puan Rohana', 'email' => 'rohana@example.com', 'staff_no' => 'FIN009', 'phone' => '03-1234 5678',
            'role' => 'accountant', 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertRedirect('/users');

        $user = User::where('email', 'rohana@example.com')->firstOrFail();
        $this->assertSame('accountant', $user->role);
        $this->assertSame('FIN009', $user->staff_no);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_management_and_lecturer_roles_can_be_given(): void
    {
        $this->actingAsRole('super_admin');

        foreach (['management' => 'ceo@example.com', 'lecturer' => 'lec@example.com'] as $role => $email) {
            $this->post('/users', [
                'name' => 'Someone', 'email' => $email, 'role' => $role,
                'password' => 'password123', 'password_confirmation' => 'password123',
            ])->assertRedirect('/users');
            $this->assertSame($role, User::where('email', $email)->value('role'));
        }

        $this->post('/users', [
            'name' => 'Old role', 'email' => 'old@example.com', 'role' => 'teacher',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('role');
    }

    public function test_student_role_cannot_be_given_from_the_accounts_page(): void
    {
        $this->actingAsRole('super_admin');

        $this->post('/users', [
            'name' => 'X', 'email' => 'x@example.com', 'role' => 'student',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('role');
    }

    public function test_super_admin_cannot_delete_or_demote_themselves(): void
    {
        $admin = $this->actingAsRole('super_admin');

        $this->delete("/users/{$admin->id}")->assertSessionHas('error');
        $this->put("/users/{$admin->id}", ['name' => $admin->name, 'email' => $admin->email, 'role' => 'lecturer']);

        $this->assertSame('super_admin', $admin->fresh()->role);
    }

    public function test_changing_a_lecturers_role_takes_them_off_subjects_and_sessions(): void
    {
        $this->actingAsRole('super_admin');
        $lecturer = User::factory()->role('lecturer')->create();
        $subject = Subject::factory()->create();
        $subject->lecturers()->attach($lecturer);
        $session = ClassSession::factory()->create(['term_id' => Term::factory()->current()->create()->id, 'subject_id' => $subject->id]);
        $session->lecturers()->attach($lecturer);

        $this->put("/users/{$lecturer->id}", ['name' => $lecturer->name, 'email' => $lecturer->email, 'role' => 'hod'])->assertRedirect();

        $this->assertSame(0, $subject->lecturers()->count());
        $this->assertSame(0, $session->lecturers()->count());
    }

    public function test_changing_a_hods_role_leaves_their_departments_without_a_head(): void
    {
        $this->actingAsRole('super_admin');
        $hod = User::factory()->role('hod')->create();
        $departments = Department::factory()->count(2)->create(['hod_id' => $hod->id]);

        $this->put("/users/{$hod->id}", ['name' => $hod->name, 'email' => $hod->email, 'role' => 'lecturer'])->assertRedirect();

        $this->assertSame(0, Department::whereNotNull('hod_id')->count());
        $this->assertSame(2, $departments->count());
    }

    public function test_deleting_staff_keeps_their_subjects_and_departments(): void
    {
        $this->actingAsRole('super_admin');
        $hod = User::factory()->role('hod')->create();
        $department = Department::factory()->create(['hod_id' => $hod->id]);
        $lecturer = User::factory()->role('lecturer')->create();
        $subject = Subject::factory()->create(['department_id' => $department->id]);
        $subject->lecturers()->attach($lecturer);

        $this->delete("/users/{$hod->id}")->assertRedirect('/users');
        $this->delete("/users/{$lecturer->id}")->assertRedirect('/users');

        $this->assertNull($department->fresh()->hod_id);
        $this->assertSame(0, $subject->lecturers()->count());
        $this->assertNotNull($subject->fresh());
    }

    public function test_accounts_can_be_filtered_by_role(): void
    {
        $this->actingAsRole('super_admin');
        User::factory()->role('lecturer')->create(['name' => 'Lecturer Person']);
        User::factory()->role('accountant')->create(['name' => 'Money Person']);

        $this->get('/users?role=lecturer')->assertSee('Lecturer Person')->assertDontSee('Money Person');
    }

    public function test_staff_update_their_own_profile_and_password(): void
    {
        $user = User::factory()->role('registrar')->create(['password' => 'old-password']);
        $this->actingAs($user);

        $this->put('/profile', ['name' => 'Zuzu', 'email' => 'zuzu@example.com', 'phone' => '012-0000000'])->assertSessionHas('success');
        $this->assertSame('Zuzu', $user->fresh()->name);

        $this->put('/profile/password', ['current_password' => 'wrong', 'password' => 'new-password', 'password_confirmation' => 'new-password'])
            ->assertSessionHasErrors('current_password');
        $this->put('/profile/password', ['current_password' => 'old-password', 'password' => 'new-password', 'password_confirmation' => 'new-password'])
            ->assertRedirect('/dashboard');
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }
}

<?php

namespace Tests\Feature;

use App\Models\Subject;
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
        $this->put("/users/{$admin->id}", ['name' => $admin->name, 'email' => $admin->email, 'role' => 'teacher']);

        $this->assertSame('super_admin', $admin->fresh()->role);
    }

    public function test_changing_a_teachers_role_unassigns_their_subjects(): void
    {
        $this->actingAsRole('super_admin');
        $teacher = User::factory()->role('teacher')->create();
        $subject = Subject::factory()->create(['teacher_id' => $teacher->id]);

        $this->put("/users/{$teacher->id}", ['name' => $teacher->name, 'email' => $teacher->email, 'role' => 'hod'])->assertRedirect();

        $this->assertNull($subject->fresh()->teacher_id);
    }

    public function test_accounts_can_be_filtered_by_role(): void
    {
        $this->actingAsRole('super_admin');
        User::factory()->role('teacher')->create(['name' => 'Teacher Person']);
        User::factory()->role('accountant')->create(['name' => 'Money Person']);

        $this->get('/users?role=teacher')->assertSee('Teacher Person')->assertDontSee('Money Person');
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

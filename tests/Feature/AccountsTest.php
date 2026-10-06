<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_cannot_open_staff_accounts(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/users')->assertForbidden();
    }

    public function test_admin_can_create_an_account_that_can_log_in(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->post('/users', [
            'name' => 'Puan Rohana', 'email' => 'rohana@example.com', 'role' => 'staff',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertRedirect('/users');

        $user = User::where('email', 'rohana@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('password123', $user->password));   // stored hashed, not plain text
        $this->assertSame('staff', $user->role);
    }

    public function test_admin_can_edit_an_account_without_changing_its_password(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $user = User::factory()->create(['password' => 'original-pass']);

        $this->put("/users/{$user->id}", ['name' => 'New Name', 'email' => $user->email, 'role' => 'admin', 'password' => ''])
            ->assertRedirect('/users');

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertSame('admin', $user->role);
        $this->assertTrue(Hash::check('original-pass', $user->password));
    }

    public function test_admin_cannot_delete_or_demote_themselves(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $this->delete("/users/{$admin->id}")->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);

        $this->put("/users/{$admin->id}", ['name' => $admin->name, 'email' => $admin->email, 'role' => 'staff']);
        $this->assertSame('admin', $admin->fresh()->role);
    }

    public function test_user_can_update_profile_and_change_password(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);
        $this->actingAs($user);

        $this->get('/profile')->assertOk();
        $this->put('/profile', ['name' => 'Zuzu', 'email' => 'zuzu@example.com'])->assertSessionHas('success');
        $this->assertSame('Zuzu', $user->fresh()->name);

        $this->put('/profile/password', [
            'current_password' => 'wrong', 'password' => 'new-password', 'password_confirmation' => 'new-password',
        ])->assertSessionHasErrors('current_password');

        $this->put('/profile/password', [
            'current_password' => 'old-password', 'password' => 'new-password', 'password_confirmation' => 'new-password',
        ])->assertSessionHas('success');
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }
}

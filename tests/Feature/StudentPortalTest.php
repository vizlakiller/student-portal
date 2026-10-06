<?php

namespace Tests\Feature;

use App\Models\Result;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentPortalTest extends TestCase
{
    use RefreshDatabase;

    private function studentLogin(array $userAttributes = []): array
    {
        $user = User::factory()->role('student')->create($userAttributes);
        $student = Student::factory()->create(['user_id' => $user->id, 'email' => $user->email]);

        return [$user, $student];
    }

    public function test_new_student_must_choose_a_password_first(): void
    {
        [$user, $student] = $this->studentLogin(['password' => 'DCS2026001', 'must_change_password' => true]);
        $this->actingAs($user);

        $this->get('/my-results')->assertRedirect('/profile');
        $this->get('/profile')->assertOk()->assertSee('This is your first login');

        $this->put('/profile/password', [
            'current_password' => 'DCS2026001', 'password' => 'my-new-password', 'password_confirmation' => 'my-new-password',
        ])->assertRedirect('/dashboard');

        $this->assertFalse($user->fresh()->must_change_password);
        $this->get('/dashboard')->assertRedirect('/my-results');
        $this->get('/my-results')->assertOk();
    }

    public function test_student_sees_their_own_subjects_and_results(): void
    {
        [$user, $student] = $this->studentLogin();
        $marked = Result::factory()->for($student)->create(['marks' => 82]);
        $pending = Result::factory()->for($student)->create(['marks' => null]);

        $this->actingAs($user)->get('/my-results')->assertOk()
            ->assertSee($marked->subject->name)
            ->assertSee($pending->subject->name)
            ->assertSee('Not out yet')
            ->assertSee('4.00');

        $this->get('/my-results/transcript')->assertOk()->assertSee($marked->subject->name)->assertDontSee($pending->subject->name);
    }

    public function test_student_cannot_see_other_students_or_change_anything(): void
    {
        [$user] = $this->studentLogin();
        $other = Result::factory()->create(['marks' => 50]);

        $this->actingAs($user);
        $this->get("/students/{$other->student_id}")->assertForbidden();
        $this->get("/students/{$other->student_id}/transcript")->assertForbidden();
        $this->put("/students/{$other->student_id}", ['name' => 'x'])->assertForbidden();
        $this->put('/profile', ['name' => 'New name', 'email' => 'new@example.com'])->assertForbidden();
    }
}

<?php

namespace Tests\Feature;

use App\Models\Result;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Logs in as every role and checks each page is allowed (200) or blocked (403)
 * exactly as described in config/roles.php.
 */
class RolePermissionsTest extends TestCase
{
    use RefreshDatabase;

    private const ROLES = ['super_admin', 'admin_staff', 'hod', 'registrar', 'teacher', 'accountant', 'student'];

    public function test_each_role_can_open_only_its_own_pages(): void
    {
        $teacher = User::factory()->role('teacher')->create();
        $subject = Subject::factory()->create(['teacher_id' => $teacher->id]);
        $result = Result::factory()->for($subject)->create(['marks' => 70]);
        $student = $result->student;
        Subject::factory()->create();   // a subject the student can still register for
        $payment = $student->payments()->create(['receipt_no' => 'RCP-2026-00001', 'amount' => 100, 'method' => 'Cash', 'paid_at' => now()]);

        $pages = [
            '/students'                                  => ['super_admin', 'admin_staff', 'hod', 'registrar', 'accountant'],
            '/students/create'                           => ['super_admin', 'registrar'],
            "/students/{$student->id}"                   => ['super_admin', 'admin_staff', 'hod', 'registrar', 'accountant'],
            "/students/{$student->id}/edit"              => ['super_admin', 'admin_staff', 'registrar'],
            "/students/{$student->id}/transcript"        => ['super_admin', 'hod', 'registrar'],
            "/students/{$student->id}/registrations/create" => ['super_admin', 'registrar'],
            "/students/{$student->id}/results/{$result->id}/edit" => ['super_admin'],
            '/teachers'                                  => ['super_admin', 'admin_staff', 'hod', 'registrar'],
            "/teachers/{$teacher->id}"                   => ['super_admin', 'admin_staff', 'hod', 'registrar'],
            '/subjects'                                  => ['super_admin', 'admin_staff', 'hod', 'registrar'],
            '/subjects/create'                           => ['super_admin', 'hod'],
            "/subjects/{$subject->id}/edit"              => ['super_admin', 'hod'],
            '/programmes'                                => ['super_admin', 'hod', 'registrar'],
            '/programmes/create'                         => ['super_admin', 'registrar'],
            '/mark-changes'                              => ['super_admin', 'hod', 'teacher'],
            "/results/{$result->id}/mark-change"         => ['super_admin', 'hod'],
            '/my-subjects'                               => ['teacher'],
            '/finance'                                   => ['super_admin', 'accountant'],
            "/finance/students/{$student->id}"           => ['super_admin', 'accountant'],
            "/finance/payments/{$payment->id}/receipt"   => ['super_admin', 'accountant'],
            '/finance/billing'                           => ['super_admin', 'accountant'],
            '/users'                                     => ['super_admin'],
            '/users/create'                              => ['super_admin'],
            '/branding'                                  => ['super_admin'],
            '/my-results'                                => ['student'],
            '/profile'                                   => self::ROLES,
        ];

        foreach (self::ROLES as $role) {
            $user = User::factory()->role($role)->create();
            if ($role === 'student') {
                Student::factory()->create(['user_id' => $user->id]);
            }
            $this->actingAs($user);

            foreach ($pages as $url => $allowed) {
                $expected = in_array($role, $allowed, true) ? 200 : 403;
                $status = $this->get($url)->getStatusCode();

                $this->assertSame($expected, $status, "{$role} opening {$url}: expected {$expected}, got {$status}");
            }
        }
    }

    public function test_the_subject_teacher_can_enter_marks_but_other_teachers_cannot(): void
    {
        $teacher = User::factory()->role('teacher')->create();
        $subject = Subject::factory()->create(['teacher_id' => $teacher->id]);

        $this->actingAs($teacher)->get("/subjects/{$subject->id}/marks")->assertOk();
        $this->actingAsRole('teacher');
        $this->get("/subjects/{$subject->id}/marks")->assertForbidden();
        $this->actingAsRole('hod');
        $this->get("/subjects/{$subject->id}/marks")->assertForbidden();
    }

    public function test_menu_shows_only_allowed_links(): void
    {
        $this->actingAsRole('accountant');
        $this->get('/finance')->assertSee('Fees and payments')->assertDontSee('User accounts')->assertDontSee('Branding');

        $this->actingAsRole('admin_staff');
        $this->get('/students')->assertSee('Teachers')->assertDontSee('Fees and payments')->assertDontSee('Mark changes');
    }
}

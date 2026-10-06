<?php

namespace Tests\Feature;

use App\Models\ClassSession;
use App\Models\Classroom;
use App\Models\Department;
use App\Models\Result;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
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

    private const ROLES = ['super_admin', 'management', 'admin_staff', 'hod', 'registrar', 'lecturer', 'accountant', 'student'];

    public function test_each_role_can_open_only_its_own_pages(): void
    {
        $term = Term::factory()->current()->create();
        $lecturer = User::factory()->role('lecturer')->create();
        $department = Department::factory()->create();
        $subject = Subject::factory()->create(['department_id' => $department->id]);
        $subject->lecturers()->attach($lecturer);
        $session = ClassSession::factory()->create(['term_id' => $term->id, 'subject_id' => $subject->id]);
        $session->lecturers()->attach($lecturer);
        $classroom = Classroom::factory()->create();
        $result = Result::factory()->for($subject)->create(['marks' => 70, 'class_session_id' => $session->id]);
        $student = $result->student;
        Subject::factory()->create();                                   // something left to register for
        ClassSession::factory()->create(['term_id' => $term->id]);
        $payment = $student->payments()->create(['receipt_no' => 'RCP-2026-00001', 'amount' => 100, 'method' => 'Cash', 'paid_at' => now()]);

        $pages = [
            '/statistics'                                => ['super_admin', 'management'],
            '/students'                                  => ['super_admin', 'management', 'admin_staff', 'hod', 'registrar', 'accountant'],
            '/students/create'                           => ['super_admin', 'registrar'],
            "/students/{$student->id}"                   => ['super_admin', 'management', 'admin_staff', 'hod', 'registrar', 'accountant'],
            "/students/{$student->id}/edit"              => ['super_admin', 'admin_staff', 'registrar'],
            "/students/{$student->id}/transcript"        => ['super_admin', 'management', 'hod', 'registrar'],
            "/students/{$student->id}/registrations/create" => ['super_admin', 'registrar'],
            "/students/{$student->id}/results/{$result->id}/edit" => ['super_admin'],
            '/lecturers'                                 => ['super_admin', 'management', 'admin_staff', 'hod', 'registrar'],
            "/lecturers/{$lecturer->id}"                 => ['super_admin', 'management', 'admin_staff', 'hod', 'registrar'],
            '/subjects'                                  => ['super_admin', 'management', 'admin_staff', 'hod', 'registrar'],
            '/subjects/create'                           => ['super_admin', 'hod'],
            '/programmes'                                => ['super_admin', 'management', 'hod', 'registrar'],
            '/programmes/create'                         => ['super_admin', 'registrar'],
            '/departments'                               => ['super_admin', 'management', 'admin_staff', 'hod', 'registrar'],
            '/departments/create'                        => ['super_admin'],
            '/timetable'                                 => ['super_admin', 'management', 'admin_staff', 'hod', 'registrar', 'lecturer'],
            '/sessions'                                  => ['super_admin', 'management', 'admin_staff', 'hod', 'registrar', 'lecturer'],
            "/sessions/{$session->id}"                   => ['super_admin', 'management', 'admin_staff', 'hod', 'registrar', 'lecturer'],
            '/sessions/create'                           => ['super_admin', 'hod'],
            '/classrooms'                                => ['super_admin', 'management', 'admin_staff', 'hod', 'registrar', 'lecturer'],
            "/classrooms/{$classroom->id}"               => ['super_admin', 'management', 'admin_staff', 'hod', 'registrar', 'lecturer'],
            '/classrooms/create'                         => ['super_admin', 'admin_staff'],
            '/terms'                                     => ['super_admin'],
            '/my-timetable'                              => ['lecturer', 'student'],
            '/my-sessions'                               => ['lecturer'],
            '/mark-changes'                              => ['super_admin', 'hod', 'lecturer'],
            '/finance'                                   => ['super_admin', 'management', 'accountant'],
            "/finance/students/{$student->id}"           => ['super_admin', 'management', 'accountant'],
            "/finance/payments/{$payment->id}/receipt"   => ['super_admin', 'management', 'accountant'],
            '/finance/billing'                           => ['super_admin', 'accountant'],
            "/finance/students/{$student->id}/payments/create" => ['super_admin', 'accountant'],
            '/users'                                     => ['super_admin'],
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

    public function test_only_the_sessions_own_lecturers_can_enter_its_marks(): void
    {
        $lecturer = User::factory()->role('lecturer')->create();
        $session = ClassSession::factory()->create();
        $session->lecturers()->attach($lecturer);

        $this->actingAs($lecturer)->get("/sessions/{$session->id}/marks")->assertOk();

        $this->actingAsRole('lecturer');
        $this->get("/sessions/{$session->id}/marks")->assertForbidden();

        $this->actingAsRole('hod');
        $this->get("/sessions/{$session->id}/marks")->assertForbidden();
    }

    public function test_management_can_view_fees_but_not_change_them(): void
    {
        $student = Student::factory()->create();
        $this->actingAsRole('management');

        $this->get('/finance')->assertOk()->assertDontSee('Bill a programme')->assertDontSee('Record payment');
        $this->get("/finance/students/{$student->id}")->assertOk()->assertDontSee('Add charge');
        $this->post("/finance/students/{$student->id}/payments", ['amount' => 10, 'method' => 'Cash', 'paid_at' => now()->format('Y-m-d')])->assertForbidden();
        $this->post('/finance/billing', ['description' => 'x', 'amount' => 1])->assertForbidden();
    }

    public function test_menu_shows_only_allowed_links(): void
    {
        $this->actingAsRole('accountant');
        $this->get('/finance')->assertSee('Fees and payments')->assertDontSee('User accounts')->assertDontSee('Weekly timetable');

        $this->actingAsRole('admin_staff');
        $this->get('/students')->assertSee('Lecturers')->assertSee('Classrooms')->assertDontSee('Fees and payments')->assertDontSee('Mark changes');

        $this->actingAsRole('management');
        $this->get('/students')->assertSee('Statistics')->assertSee('Fees and payments')->assertDontSee('User accounts');
    }
}

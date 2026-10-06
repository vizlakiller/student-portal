<?php

namespace Tests\Feature;

use App\Models\Programme;
use App\Models\Result;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentTest extends TestCase
{
    use RefreshDatabase;

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'student_no'    => 'DCS2026099',
            'name'          => 'Aminah binti Yusof',
            'email'         => 'aminah@example.com',
            'phone'         => '012-3456789',
            'gender'        => 'Female',
            'date_of_birth' => '2007-05-14',
            'address'       => 'No. 1, Jalan Mawar, Melaka',
            'programme_id'  => Programme::factory()->create()->id,
            'intake_year'   => 2026,
            'semester'      => 1,
            'status'        => 'Active',
        ], $overrides);
    }

    public function test_registrar_adds_a_student_and_their_login_is_created(): void
    {
        $this->actingAsRole('registrar');

        $response = $this->post('/students', $this->validData());

        $student = Student::where('student_no', 'DCS2026099')->firstOrFail();
        $response->assertRedirect(route('students.show', $student));

        $login = $student->user;
        $this->assertSame('student', $login->role);
        $this->assertSame('aminah@example.com', $login->email);
        $this->assertTrue(Hash::check('DCS2026099', $login->password));   // first password = student number
        $this->assertTrue($login->must_change_password);
    }

    public function test_student_email_cannot_belong_to_another_login(): void
    {
        $this->actingAsRole('registrar');
        User::factory()->create(['email' => 'aminah@example.com']);

        $this->post('/students', $this->validData())->assertSessionHasErrors('email');
    }

    public function test_student_number_must_be_unique_and_fields_are_required(): void
    {
        $this->actingAsRole('registrar');
        Student::factory()->create(['student_no' => 'DCS2026099']);

        $this->post('/students', $this->validData())->assertSessionHasErrors('student_no');
        $this->post('/students', [])->assertSessionHasErrors(['student_no', 'name', 'email', 'gender', 'programme_id', 'intake_year', 'semester', 'status']);
    }

    public function test_admin_staff_can_change_contact_details_but_not_enrolment(): void
    {
        $this->actingAsRole('admin_staff');
        $student = Student::factory()->create(['semester' => 2, 'status' => 'Active']);
        $student->user()->associate(User::factory()->role('student')->create(['email' => $student->email]))->save();

        $this->put("/students/{$student->id}", [
            'name' => 'New Name', 'email' => 'new@example.com', 'phone' => '019-1111111',
            'gender' => $student->gender, 'address' => 'New address',
            'semester' => 5, 'status' => 'Withdrawn', 'student_no' => 'HACKED',   // should be ignored
        ])->assertRedirect(route('students.show', $student));

        $student->refresh();
        $this->assertSame('New Name', $student->name);
        $this->assertSame('019-1111111', $student->phone);
        $this->assertSame(2, $student->semester);
        $this->assertSame('Active', $student->status);
        $this->assertNotSame('HACKED', $student->student_no);
        $this->assertSame('new@example.com', $student->user->email);   // login follows the record
    }

    public function test_registrar_can_change_enrolment(): void
    {
        $this->actingAsRole('registrar');
        $student = Student::factory()->create();

        $this->put("/students/{$student->id}", $this->validData([
            'student_no' => $student->student_no, 'email' => $student->email, 'semester' => 3,
        ]))->assertRedirect(route('students.show', $student));

        $this->assertSame(3, $student->fresh()->semester);
    }

    public function test_results_are_hidden_from_admin_staff_and_accountant_but_shown_to_registrar(): void
    {
        $result = Result::factory()->create(['marks' => 85]);
        $url = "/students/{$result->student_id}";

        $this->actingAsRole('admin_staff');
        $this->get($url)->assertOk()->assertDontSee('CGPA')->assertDontSee($result->subject->name);

        $this->actingAsRole('accountant');
        $this->get($url)->assertOk()->assertDontSee('CGPA');

        $this->actingAsRole('registrar');
        $this->get($url)->assertOk()->assertSee('CGPA')->assertSee($result->subject->name)->assertDontSee('Request change');
    }

    public function test_profile_and_transcript_show_the_cgpa(): void
    {
        $this->actingAsRole('registrar');
        $student = Student::factory()->create();
        Result::factory()->for($student)->for(Subject::factory()->create(['credit_hours' => 3]))->create(['marks' => 85, 'semester' => 1]); // 4.00
        Result::factory()->for($student)->for(Subject::factory()->create(['credit_hours' => 3]))->create(['marks' => 66, 'semester' => 1]); // 3.00
        Result::factory()->for($student)->create(['marks' => null, 'semester' => 2]);                                                       // not marked: ignored

        $this->get("/students/{$student->id}")->assertOk()->assertSee('3.50')->assertSee('Not marked');
        $this->get("/students/{$student->id}/transcript")->assertOk()->assertSee('Academic transcript')->assertSee('3.50');
    }

    public function test_only_super_admin_deletes_a_student_and_their_login_goes_too(): void
    {
        $student = Student::factory()->create();
        $student->user()->associate(User::factory()->role('student')->create())->save();
        $student->charges()->create(['description' => 'Fee', 'amount' => 100]);

        $this->actingAsRole('registrar');
        $this->delete("/students/{$student->id}")->assertForbidden();

        $this->actingAsRole('super_admin');
        $this->delete("/students/{$student->id}")->assertRedirect('/students');
        $this->assertDatabaseMissing('students', ['id' => $student->id]);
        $this->assertDatabaseMissing('users', ['id' => $student->user_id]);
        $this->assertDatabaseCount('charges', 0);
    }

    public function test_registrar_can_reset_a_student_password(): void
    {
        $this->actingAsRole('registrar');
        $student = Student::factory()->create(['student_no' => 'DIT2025012']);
        $student->user()->associate(User::factory()->role('student')->create(['password' => 'my-own-password']))->save();

        $this->post("/students/{$student->id}/reset-password")->assertSessionHas('success');

        $login = $student->user->fresh();
        $this->assertTrue(Hash::check('DIT2025012', $login->password));
        $this->assertTrue($login->must_change_password);
    }

    public function test_students_can_be_searched_filtered_and_exported(): void
    {
        $this->actingAsRole('admin_staff');
        $dcs = Programme::factory()->create();
        Student::factory()->create(['name' => 'Ahmad Faiz', 'programme_id' => $dcs->id]);
        Student::factory()->create(['name' => 'Lim Mei Ling', 'status' => 'Graduated']);

        $this->get('/students?search=Faiz')->assertSee('Ahmad Faiz')->assertDontSee('Lim Mei Ling');
        $this->get('/students?programme='.$dcs->id)->assertSee('Ahmad Faiz')->assertDontSee('Lim Mei Ling');
        $this->get('/students?status=Graduated')->assertSee('Lim Mei Ling')->assertDontSee('Ahmad Faiz');

        $csv = $this->get('/students/export?status=Graduated')->assertOk()->streamedContent();
        $this->assertStringContainsString('"Student No",Name,Email', $csv);
        $this->assertStringContainsString('Lim Mei Ling', $csv);
        $this->assertStringNotContainsString('Ahmad Faiz', $csv);
    }
}

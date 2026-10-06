<?php

namespace Tests\Feature;

use App\Models\Programme;
use App\Models\Result;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

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

    public function test_students_list_shows_students(): void
    {
        $student = Student::factory()->create(['name' => 'Tan Wei Ming']);

        $this->get('/students')->assertOk()->assertSee('Tan Wei Ming')->assertSee($student->student_no);
    }

    public function test_students_can_be_searched_and_filtered(): void
    {
        $dcs = Programme::factory()->create();
        Student::factory()->create(['name' => 'Ahmad Faiz', 'programme_id' => $dcs->id]);
        Student::factory()->create(['name' => 'Lim Mei Ling', 'status' => 'Graduated']);

        $this->get('/students?search=Faiz')->assertSee('Ahmad Faiz')->assertDontSee('Lim Mei Ling');
        $this->get('/students?programme='.$dcs->id)->assertSee('Ahmad Faiz')->assertDontSee('Lim Mei Ling');
        $this->get('/students?status=Graduated')->assertSee('Lim Mei Ling')->assertDontSee('Ahmad Faiz');
    }

    public function test_a_student_can_be_added(): void
    {
        $response = $this->post('/students', $this->validData());

        $student = Student::where('student_no', 'DCS2026099')->firstOrFail();
        $response->assertRedirect(route('students.show', $student));
        $this->assertSame('Aminah binti Yusof', $student->name);
    }

    public function test_student_number_and_email_must_be_unique(): void
    {
        Student::factory()->create(['student_no' => 'DCS2026099', 'email' => 'aminah@example.com']);

        $this->post('/students', $this->validData())
            ->assertSessionHasErrors(['student_no', 'email']);
    }

    public function test_required_fields_are_validated(): void
    {
        $this->post('/students', [])
            ->assertSessionHasErrors(['student_no', 'name', 'email', 'gender', 'programme_id', 'intake_year', 'semester', 'status']);

        $this->post('/students', $this->validData(['email' => 'not-an-email', 'status' => 'Unknown']))
            ->assertSessionHasErrors(['email', 'status']);
    }

    public function test_a_student_can_be_updated_keeping_their_own_number(): void
    {
        $student = Student::factory()->create();

        $this->put("/students/{$student->id}", $this->validData([
            'student_no' => $student->student_no,
            'email'      => $student->email,
            'semester'   => 3,
        ]))->assertRedirect(route('students.show', $student));

        $this->assertSame(3, $student->fresh()->semester);
    }

    public function test_deleting_a_student_also_deletes_their_results(): void
    {
        $result = Result::factory()->create();

        $this->delete("/students/{$result->student_id}")->assertRedirect('/students');

        $this->assertDatabaseMissing('students', ['id' => $result->student_id]);
        $this->assertDatabaseMissing('results', ['id' => $result->id]);
    }

    public function test_profile_and_transcript_show_the_cgpa(): void
    {
        $student = Student::factory()->create();
        $subjectA = Subject::factory()->create(['credit_hours' => 3]);
        $subjectB = Subject::factory()->create(['credit_hours' => 3]);
        Result::factory()->for($student)->for($subjectA)->create(['marks' => 85, 'semester' => 1]); // 4.00
        Result::factory()->for($student)->for($subjectB)->create(['marks' => 66, 'semester' => 1]); // 3.00

        $this->get("/students/{$student->id}")->assertOk()->assertSee('3.50')->assertSee($subjectA->name);
        $this->get("/students/{$student->id}/transcript")->assertOk()->assertSee('Academic transcript')->assertSee('3.50');
    }

    public function test_student_list_can_be_exported_as_csv(): void
    {
        Student::factory()->create(['name' => 'Ahmad Faiz']);
        Student::factory()->create(['name' => 'Lim Mei Ling', 'status' => 'Graduated']);

        $response = $this->get('/students/export?status=Graduated');

        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $csv = $response->streamedContent();
        $this->assertStringContainsString('"Student No",Name,Email', $csv);
        $this->assertStringContainsString('Lim Mei Ling', $csv);
        $this->assertStringNotContainsString('Ahmad Faiz', $csv);
    }
}

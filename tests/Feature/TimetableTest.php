<?php

namespace Tests\Feature;

use App\Models\ClassSession;
use App\Models\Classroom;
use App\Models\Department;
use App\Models\Result;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Models\TimetableSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sessions, weekly class times, classrooms and terms.
 */
class TimetableTest extends TestCase
{
    use RefreshDatabase;

    private Term $term;

    private User $hod;

    private Department $department;

    private Subject $subject;

    private User $lecturer;

    private Classroom $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->term = Term::factory()->current()->create(['name' => 'September 2026']);
        [$this->hod, $this->department] = $this->actingAsHod();

        $this->lecturer = User::factory()->role('lecturer')->create(['name' => 'Encik Faizal']);
        $this->subject = Subject::factory()->create(['code' => 'CSC1043', 'department_id' => $this->department->id]);
        $this->subject->lecturers()->attach($this->lecturer);

        $this->room = Classroom::factory()->create(['code' => 'MK-201', 'capacity' => 30]);
    }

    private function makeSession(array $attributes = [], ?Subject $subject = null, array $lecturers = []): ClassSession
    {
        $session = ClassSession::factory()->create(['term_id' => $this->term->id, 'subject_id' => ($subject ?? $this->subject)->id] + $attributes);
        $session->lecturers()->attach($lecturers ?: [$this->lecturer->id]);

        return $session;
    }

    private function addSlot(ClassSession $session, array $data = [])
    {
        return $this->post("/sessions/{$session->id}/slots", $data + [
            'day' => 1, 'starts_at' => '08:00', 'ends_at' => '10:00', 'classroom_id' => $this->room->id,
        ]);
    }

    // ---------- Sessions ----------

    public function test_hod_creates_several_sessions_for_a_subject_in_their_department(): void
    {
        $this->get('/sessions/create')->assertOk()->assertSee('CSC1043');
        $this->get("/sessions/create?subject={$this->subject->id}")->assertOk()->assertSee('Encik Faizal');

        foreach (['Group A', 'Group B'] as $name) {
            $this->post('/sessions', [
                'subject_id' => $this->subject->id, 'term_id' => $this->term->id, 'name' => $name,
                'capacity' => 30, 'lecturer_ids' => [$this->lecturer->id],
            ])->assertRedirect();
        }

        $this->assertSame(2, $this->subject->sessions()->count());
        $this->assertSame([$this->lecturer->id], $this->subject->sessions()->first()->lecturers->pluck('id')->all());

        // Same name twice in one term is refused.
        $this->post('/sessions', ['subject_id' => $this->subject->id, 'term_id' => $this->term->id, 'name' => 'Group A'])
            ->assertSessionHasErrors('name');

        $this->get('/sessions')->assertOk()->assertSee('Group A')->assertSee('Group B');
    }

    public function test_session_lecturers_must_teach_the_subject(): void
    {
        $outsider = User::factory()->role('lecturer')->create();

        $this->post('/sessions', [
            'subject_id' => $this->subject->id, 'term_id' => $this->term->id, 'name' => 'Group A', 'lecturer_ids' => [$outsider->id],
        ])->assertSessionHasErrors('lecturer_ids.0');

        $this->assertSame(0, ClassSession::count());
    }

    public function test_hod_cannot_manage_sessions_of_another_departments_subjects(): void
    {
        $otherSubject = Subject::factory()->create(['department_id' => Department::factory()->create()->id]);
        $otherSession = $this->makeSession([], $otherSubject);

        $this->get('/sessions/create')->assertDontSee($otherSubject->code);
        $this->post('/sessions', ['subject_id' => $otherSubject->id, 'term_id' => $this->term->id, 'name' => 'Group Z'])->assertForbidden();
        $this->get("/sessions/{$otherSession->id}/edit")->assertForbidden();
        $this->delete("/sessions/{$otherSession->id}")->assertForbidden();
        $this->addSlot($otherSession)->assertForbidden();

        // Viewing is fine, without the class time form.
        $this->get("/sessions/{$otherSession->id}")->assertOk()->assertDontSee('Add a class time');
        $this->assertSame(1, ClassSession::where('subject_id', $otherSubject->id)->count());
    }

    public function test_the_term_stays_fixed_when_a_session_is_edited(): void
    {
        $session = $this->makeSession(['name' => 'Group A']);
        $nextTerm = Term::factory()->create();

        $this->put("/sessions/{$session->id}", ['name' => 'Group A1', 'term_id' => $nextTerm->id, 'capacity' => 25, 'lecturer_ids' => [$this->lecturer->id]])
            ->assertRedirect(route('sessions.show', $session));

        $session->refresh();
        $this->assertSame('Group A1', $session->name);
        $this->assertSame(25, $session->capacity);
        $this->assertSame($this->term->id, $session->term_id);
    }

    public function test_session_with_students_cannot_be_deleted_but_an_empty_one_can(): void
    {
        $full = $this->makeSession(['name' => 'Group A']);
        Result::factory()->for($this->subject)->create(['class_session_id' => $full->id, 'marks' => null]);
        $empty = $this->makeSession(['name' => 'Group B']);
        $this->addSlot($empty);

        $this->delete("/sessions/{$full->id}")->assertSessionHas('error');
        $this->delete("/sessions/{$empty->id}")->assertRedirect('/sessions');

        $this->assertDatabaseHas('class_sessions', ['id' => $full->id]);
        $this->assertDatabaseMissing('class_sessions', ['id' => $empty->id]);
        $this->assertSame(0, TimetableSlot::count());   // its class times went with it
    }

    // ---------- Class times and clashes ----------

    public function test_hod_adds_and_removes_class_times(): void
    {
        $session = $this->makeSession();

        $this->get("/sessions/{$session->id}")->assertSee('Add a class time');
        $this->addSlot($session, ['day' => 2, 'starts_at' => '14:00', 'ends_at' => '16:00'])->assertSessionHas('success');

        $slot = TimetableSlot::firstOrFail();
        $this->assertSame([2, '14:00:00', '16:00:00'], [$slot->day, $slot->starts_at, $slot->ends_at]);
        $this->get("/sessions/{$session->id}")->assertSee('Tuesday')->assertSee('14:00 to 16:00');

        $this->delete("/slots/{$slot->id}")->assertSessionHas('success');
        $this->assertSame(0, TimetableSlot::count());
    }

    public function test_classes_back_to_back_are_allowed(): void
    {
        $a = $this->makeSession(['name' => 'Group A']);
        $b = $this->makeSession(['name' => 'Group B']);

        $this->addSlot($a, ['starts_at' => '08:00', 'ends_at' => '10:00'])->assertSessionHasNoErrors();
        $this->addSlot($b, ['starts_at' => '10:00', 'ends_at' => '12:00'])->assertSessionHasNoErrors();

        $this->assertSame(2, TimetableSlot::count());
    }

    public function test_room_clash_is_refused(): void
    {
        $otherLecturer = User::factory()->role('lecturer')->create();
        $other = Subject::factory()->create(['department_id' => $this->department->id]);
        $other->lecturers()->attach($otherLecturer);

        $this->addSlot($this->makeSession(), ['starts_at' => '08:00', 'ends_at' => '10:00']);
        $this->addSlot($this->makeSession([], $other, [$otherLecturer->id]), ['starts_at' => '09:00', 'ends_at' => '11:00'])
            ->assertSessionHasErrors('clash');

        $this->assertSame(1, TimetableSlot::count());
    }

    public function test_lecturer_clash_is_refused_even_in_another_room(): void
    {
        $a = $this->makeSession(['name' => 'Group A']);
        $b = $this->makeSession(['name' => 'Group B']);   // same lecturer
        $otherRoom = Classroom::factory()->create();

        $this->addSlot($a, ['starts_at' => '08:00', 'ends_at' => '10:00']);
        $response = $this->addSlot($b, ['starts_at' => '09:30', 'ends_at' => '11:00', 'classroom_id' => $otherRoom->id]);

        $response->assertSessionHasErrors('clash');
        $this->assertStringContainsString('Encik Faizal is already teaching', session('errors')->first('clash'));
        $this->assertSame(1, TimetableSlot::count());
    }

    public function test_session_cannot_have_two_classes_at_once(): void
    {
        $session = $this->makeSession();
        $otherRoom = Classroom::factory()->create();
        $session->lecturers()->detach();   // so only the session itself can clash

        $this->addSlot($session, ['starts_at' => '08:00', 'ends_at' => '10:00']);
        $this->addSlot($session, ['starts_at' => '08:00', 'ends_at' => '09:00', 'classroom_id' => $otherRoom->id])->assertSessionHasErrors('clash');
    }

    public function test_the_same_room_in_another_term_is_not_a_clash(): void
    {
        $old = Term::factory()->create();
        $oldSession = ClassSession::factory()->create(['term_id' => $old->id, 'subject_id' => $this->subject->id, 'name' => 'Group A']);
        $oldSession->lecturers()->attach($this->lecturer);
        $oldSession->slots()->create(['day' => 1, 'starts_at' => '08:00:00', 'ends_at' => '10:00:00', 'classroom_id' => $this->room->id]);

        $this->addSlot($this->makeSession(['name' => 'Group A']))->assertSessionHasNoErrors();
        $this->assertSame(2, TimetableSlot::count());
    }

    public function test_class_times_must_be_sensible(): void
    {
        $session = $this->makeSession();

        $this->addSlot($session, ['starts_at' => '07:00', 'ends_at' => '09:00'])->assertSessionHasErrors('starts_at');
        $this->addSlot($session, ['starts_at' => '19:00', 'ends_at' => '21:00'])->assertSessionHasErrors('ends_at');
        $this->addSlot($session, ['starts_at' => '10:00', 'ends_at' => '09:00'])->assertSessionHasErrors('ends_at');
        $this->addSlot($session, ['day' => 7])->assertSessionHasErrors('day');
        $this->addSlot($session, ['starts_at' => '9am'])->assertSessionHasErrors('starts_at');

        $this->assertSame(0, TimetableSlot::count());
    }

    public function test_adding_a_busy_lecturer_to_a_session_is_refused(): void
    {
        $second = User::factory()->role('lecturer')->create(['name' => 'Dr. Lim']);
        $this->subject->lecturers()->attach($second);

        $a = $this->makeSession(['name' => 'Group A'], null, [$this->lecturer->id]);
        $b = $this->makeSession(['name' => 'Group B'], null, [$second->id]);
        $this->addSlot($a, ['starts_at' => '08:00', 'ends_at' => '10:00']);
        $this->addSlot($b, ['starts_at' => '08:00', 'ends_at' => '10:00', 'classroom_id' => Classroom::factory()->create()->id]);

        // Dr. Lim teaches Group B at the same time, so can't join Group A.
        $this->put("/sessions/{$a->id}", ['name' => 'Group A', 'lecturer_ids' => [$this->lecturer->id, $second->id]])
            ->assertSessionHasErrors('lecturer_ids');
        $this->assertSame([$this->lecturer->id], $a->lecturers()->pluck('users.id')->all());
    }

    public function test_room_capacity_warning_when_the_room_is_too_small(): void
    {
        $session = $this->makeSession(['capacity' => 50]);

        $this->addSlot($session)->assertSessionHas('success', fn ($message) => str_contains($message, 'the room holds 30'));
    }

    // ---------- Timetable pages ----------

    public function test_weekly_timetable_can_be_filtered(): void
    {
        $session = $this->makeSession(['name' => 'Group A']);
        $this->addSlot($session);
        $otherRoom = Classroom::factory()->create(['code' => 'BK1-101']);

        $this->get('/timetable')->assertOk()->assertSee('CSC1043')->assertSee('MK-201');
        $this->get("/timetable?lecturer={$this->lecturer->id}")->assertSee('CSC1043');
        $this->get("/timetable?classroom={$otherRoom->id}")->assertDontSee('CSC1043 Group A');
        $this->get("/classrooms/{$this->room->id}")->assertOk()->assertSee('CSC1043');
        $this->get("/lecturers/{$this->lecturer->id}")->assertOk()->assertSee('CSC1043');
    }

    public function test_lecturer_and_student_see_their_own_timetable(): void
    {
        $mine = $this->makeSession(['name' => 'Group A']);
        $this->addSlot($mine, ['day' => 3, 'starts_at' => '10:00', 'ends_at' => '12:00']);

        $otherSubject = Subject::factory()->create(['code' => 'MAT1023', 'department_id' => $this->department->id]);
        $notMine = $this->makeSession(['name' => 'Group A'], $otherSubject, [User::factory()->role('lecturer')->create()->id]);
        $this->addSlot($notMine, ['day' => 4, 'starts_at' => '10:00', 'ends_at' => '12:00']);

        $this->actingAs($this->lecturer);
        $this->get('/my-timetable')->assertOk()->assertSee('CSC1043')->assertDontSee('MAT1023')->assertSee('Wednesday');

        $login = User::factory()->role('student')->create();
        $student = Student::factory()->create(['user_id' => $login->id, 'email' => $login->email]);
        $student->results()->create(['subject_id' => $this->subject->id, 'class_session_id' => $mine->id, 'semester' => 1]);
        $this->actingAs($login);
        $this->get('/my-timetable')->assertOk()->assertSee('CSC1043')->assertDontSee('MAT1023');

        $this->actingAsRole('accountant');
        $this->get('/my-timetable')->assertForbidden();
    }

    // ---------- Classrooms ----------

    public function test_admin_staff_manage_classrooms_and_hod_cannot(): void
    {
        $this->post('/classrooms', ['code' => 'X1', 'name' => 'X', 'type' => 'Lecture room', 'capacity' => 20])->assertForbidden();

        $this->actingAsRole('admin_staff');
        $this->post('/classrooms', ['code' => 'BK2-110', 'name' => 'Computer Lab 2', 'building' => 'Block 2', 'type' => 'Computer lab', 'capacity' => 25])
            ->assertRedirect('/classrooms');
        $room = Classroom::where('code', 'BK2-110')->firstOrFail();

        $this->put("/classrooms/{$room->id}", ['code' => 'BK2-110', 'name' => 'Computer Lab 2', 'type' => 'Computer lab', 'capacity' => 28])
            ->assertRedirect('/classrooms');
        $this->assertSame(28, $room->fresh()->capacity);

        $this->post('/classrooms', ['code' => 'BK2-110', 'name' => 'Again', 'type' => 'Garden', 'capacity' => 0])
            ->assertSessionHasErrors(['code', 'type', 'capacity']);

        $this->delete("/classrooms/{$room->id}")->assertRedirect('/classrooms');
        $this->assertDatabaseMissing('classrooms', ['id' => $room->id]);
    }

    public function test_classroom_in_use_cannot_be_deleted(): void
    {
        $this->addSlot($this->makeSession());

        $this->actingAsRole('admin_staff');
        $this->delete("/classrooms/{$this->room->id}")->assertSessionHas('error');
        $this->assertDatabaseHas('classrooms', ['id' => $this->room->id]);
    }

    // ---------- Terms ----------

    public function test_super_admin_manages_terms_and_sets_the_current_one(): void
    {
        $this->actingAsRole('super_admin');

        $this->post('/terms', ['name' => 'March 2027', 'starts_on' => '2027-03-01', 'ends_on' => '2027-07-31'])->assertRedirect('/terms');
        $next = Term::where('name', 'March 2027')->firstOrFail();
        $this->assertFalse($next->is_current);

        $this->post("/terms/{$next->id}/current")->assertRedirect('/terms');
        $this->assertTrue($next->fresh()->is_current);
        $this->assertFalse($this->term->fresh()->is_current);
        $this->assertSame(1, Term::where('is_current', true)->count());

        $this->post('/terms', ['name' => 'Bad', 'starts_on' => '2027-03-01', 'ends_on' => '2027-02-01'])->assertSessionHasErrors('ends_on');
        $this->post('/terms', ['name' => 'March 2027'])->assertSessionHasErrors('name');
    }

    public function test_term_with_sessions_or_current_cannot_be_deleted(): void
    {
        $this->actingAsRole('super_admin');
        $this->makeSession();
        $empty = Term::factory()->create();

        $this->delete("/terms/{$this->term->id}")->assertSessionHas('error');
        $this->delete("/terms/{$empty->id}")->assertRedirect('/terms');

        $this->assertDatabaseHas('terms', ['id' => $this->term->id]);
        $this->assertDatabaseMissing('terms', ['id' => $empty->id]);
    }

    public function test_only_the_super_admin_manages_terms(): void
    {
        foreach (['hod', 'admin_staff', 'registrar', 'management'] as $role) {
            $this->actingAsRole($role);
            $this->get('/terms')->assertForbidden();
            $this->post("/terms/{$this->term->id}/current")->assertForbidden();
        }
    }
}

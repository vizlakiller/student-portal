<?php

namespace App\Http\Controllers;

use App\Models\ClassSession;
use App\Models\Classroom;
use App\Models\Department;
use App\Models\Subject;
use App\Models\Term;
use App\Support\Timetable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Sessions (groups) of a subject in a term, e.g. CSC1043 Group A and Group B.
 * Heads of department manage sessions for their own departments' subjects.
 */
class ClassSessionController extends Controller
{
    public function index(Request $request)
    {
        $term = $request->filled('term') ? Term::findOrFail($request->integer('term')) : Term::current();
        $departmentId = $request->integer('department') ?: null;

        $sessions = ClassSession::query()
            ->with(['subject.department', 'lecturers', 'slots.classroom'])
            ->withCount('results')
            ->where('term_id', $term?->id)
            ->when($departmentId, fn ($query) => $query->whereHas('subject', fn ($subject) => $subject->where('department_id', $departmentId)))
            ->get()
            ->sortBy(fn ($session) => $session->subject->code.' '.$session->name)
            ->groupBy('subject_id');

        return view('sessions.index', [
            'term'         => $term,
            'terms'        => Term::orderByDesc('starts_on')->orderByDesc('id')->get(),
            'departments'  => Department::orderBy('code')->get(),
            'departmentId' => $departmentId,
            'sessions'     => $sessions,
        ]);
    }

    /**
     * Step 1: choose a subject (from your departments). Step 2: name the session and choose lecturers.
     */
    public function create(Request $request)
    {
        $subjects = $this->manageableSubjects($request);
        $subject = $request->filled('subject') ? $subjects->firstWhere('id', $request->integer('subject')) : null;

        return view('sessions.create', [
            'subjects' => $subjects,
            'subject'  => $subject?->load('lecturers'),
            'terms'    => Term::orderByDesc('starts_on')->orderByDesc('id')->get(),
            'current'  => Term::current(),
            'session'  => new ClassSession(['term_id' => Term::current()?->id]),
        ]);
    }

    public function store(Request $request)
    {
        $subject = Subject::findOrFail($request->integer('subject_id'));
        Gate::authorize('manage-subject-timetable', $subject);

        $data = $this->validateSession($request, $subject);

        $session = DB::transaction(function () use ($data, $subject) {
            $session = ClassSession::create([
                'term_id'    => $data['term_id'],
                'subject_id' => $subject->id,
                'name'       => $data['name'],
                'capacity'   => $data['capacity'] ?? null,
            ]);
            $session->lecturers()->sync($data['lecturer_ids'] ?? []);

            return $session;
        });

        return redirect()->route('sessions.show', $session)
            ->with('success', "Session {$subject->code} {$session->name} added. Now add its class times.");
    }

    public function show(ClassSession $classSession)
    {
        $classSession->load(['term', 'subject.department', 'lecturers', 'slots.classroom', 'results.student']);

        return view('sessions.show', [
            'session'    => $classSession,
            'classrooms' => Classroom::orderBy('code')->get(),
            'days'       => Timetable::DAYS,
            'students'   => $classSession->results->sortBy('student.name'),
        ]);
    }

    public function edit(ClassSession $classSession)
    {
        Gate::authorize('manage-subject-timetable', $classSession->subject);

        $classSession->load(['subject.lecturers', 'lecturers']);

        return view('sessions.edit', [
            'session' => $classSession,
            'subject' => $classSession->subject,
        ]);
    }

    public function update(Request $request, ClassSession $classSession)
    {
        Gate::authorize('manage-subject-timetable', $classSession->subject);

        // The term stays fixed once a session exists (its class times belong to that term).
        $request->merge(['term_id' => $classSession->term_id]);
        $data = $this->validateSession($request, $classSession->subject, $classSession);

        // A newly added lecturer must be free at all of this session's class times.
        $added = collect($data['lecturer_ids'] ?? [])->diff($classSession->lecturers()->pluck('users.id'));
        foreach ($classSession->slots as $slot) {
            $clashes = Timetable::lecturerClashes($added, $classSession->term_id, $slot->day, $slot->starts_at, $slot->ends_at, $classSession->id);
            if ($clashes) {
                return back()->withInput()->withErrors(['lecturer_ids' => $clashes[0]]);
            }
        }

        DB::transaction(function () use ($classSession, $data) {
            $classSession->update([
                'name'     => $data['name'],
                'capacity' => $data['capacity'] ?? null,
            ]);
            $classSession->lecturers()->sync($data['lecturer_ids'] ?? []);
        });

        return redirect()->route('sessions.show', $classSession)->with('success', 'Session updated.');
    }

    public function destroy(ClassSession $classSession)
    {
        Gate::authorize('manage-subject-timetable', $classSession->subject);

        if ($classSession->results()->exists()) {
            return back()->with('error', 'Students are registered in this session. Move or remove them first.');
        }

        $label = $classSession->label();
        $classSession->delete();   // its class times go too

        return redirect()->route('sessions.index')->with('success', "Session {$label} deleted.");
    }

    /** Subjects this user may create sessions for. */
    private function manageableSubjects(Request $request)
    {
        $user = $request->user();

        return Subject::orderBy('code')
            ->when(! $user->isSuperAdmin(), fn ($query) => $query->whereIn('department_id', $user->headedDepartmentIds()))
            ->get();
    }

    private function validateSession(Request $request, Subject $subject, ?ClassSession $session = null): array
    {
        $subjectLecturers = $subject->lecturers()->pluck('users.id')->all();

        return $request->validate([
            'term_id'        => ['required', 'exists:terms,id'],
            'name'           => ['required', 'string', 'max:60',
                Rule::unique('class_sessions')->where('subject_id', $subject->id)->where('term_id', $request->input('term_id'))->ignore($session)],
            'capacity'       => ['nullable', 'integer', 'between:1,1000'],
            'lecturer_ids'   => ['array'],
            'lecturer_ids.*' => ['integer', Rule::in($subjectLecturers)],
        ], [
            'name.unique'        => 'This subject already has a session with that name in this term.',
            'lecturer_ids.*.in'  => 'Choose lecturers who are assigned to this subject (on the Subjects page).',
        ], ['term_id' => 'term']);
    }
}

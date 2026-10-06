<?php

namespace App\Http\Controllers;

use App\Models\ClassSession;
use App\Models\Student;
use App\Models\Term;
use App\Models\TimetableSlot;
use App\Support\Timetable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Registrar: register a student for subjects in the current term, choosing
 * one session (group) per subject. Marks stay empty until the session's
 * lecturer enters them.
 */
class RegistrationController extends Controller
{
    public function create(Student $student)
    {
        $term = Term::current();

        if (! $term) {
            return redirect()->route('students.show', $student)
                ->with('error', 'There is no current term yet. The super admin sets it under Terms.');
        }

        $taken = $student->results()->pluck('subject_id');

        // Sessions this term for subjects the student hasn't taken, grouped by subject
        $sessions = ClassSession::with(['subject', 'lecturers', 'slots.classroom'])
            ->withCount('results')
            ->where('term_id', $term->id)
            ->whereNotIn('subject_id', $taken)
            ->get()
            ->sortBy(fn ($session) => $session->subject->code.' '.$session->name)
            ->groupBy('subject_id');

        if ($sessions->isEmpty()) {
            return redirect()->route('students.show', $student)
                ->with('error', "No sessions are open in {$term->name} for subjects this student still needs. Heads of department add sessions under Timetable.");
        }

        return view('registrations.create', compact('student', 'term', 'sessions'));
    }

    public function store(Request $request, Student $student)
    {
        $term = Term::current();
        abort_unless($term, 422, 'There is no current term.');

        $data = $request->validate([
            'sessions'   => ['array'],
            'sessions.*' => ['nullable', 'integer'],
            'semester'   => ['required', 'integer', 'between:1,12'],
        ]);

        $chosenIds = collect($data['sessions'] ?? [])->filter()->values();
        if ($chosenIds->isEmpty()) {
            throw ValidationException::withMessages(['sessions' => 'Choose a session for at least one subject.']);
        }

        $chosen = ClassSession::with(['subject', 'slots.classSession.subject'])->withCount('results')->whereIn('id', $chosenIds)->get();
        $taken = $student->results()->pluck('subject_id');

        foreach ($chosen as $session) {
            $problem = match (true) {
                $session->term_id !== $term->id         => "{$session->label()} is not in the current term.",
                $taken->contains($session->subject_id)  => "The student is already registered for {$session->subject->code}.",
                $session->isFull($session->results_count) => "{$session->label()} is full ({$session->capacity} students).",
                default                                  => null,
            };

            if ($problem) {
                throw ValidationException::withMessages(['sessions' => $problem]);
            }
        }

        if ($chosen->pluck('subject_id')->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages(['sessions' => 'Choose only one session per subject.']);
        }

        // The new class times must not clash with each other or with the student's other classes this term.
        $existing = TimetableSlot::with('classSession.subject')
            ->whereHas('classSession', fn ($q) => $q->where('term_id', $term->id)
                ->whereHas('results', fn ($r) => $r->where('student_id', $student->id)))
            ->get();

        if ($clash = Timetable::firstClashBetween($existing->concat($chosen->flatMap->slots))) {
            throw ValidationException::withMessages(['sessions' => "Timetable clash: {$clash} Choose another session."]);
        }

        DB::transaction(function () use ($student, $chosen, $data) {
            foreach ($chosen as $session) {
                $student->results()->create([
                    'subject_id'       => $session->subject_id,
                    'class_session_id' => $session->id,
                    'semester'         => $data['semester'],
                ]);
            }
        });

        $count = $chosen->count();

        return redirect()->route('students.show', $student)
            ->with('success', "Registered for {$count} ".str('subject')->plural($count).' in '.$term->name.'. Their lecturers can now enter marks.');
    }
}

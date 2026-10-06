<?php

namespace App\Http\Controllers;

use App\Models\Term;
use App\Models\User;
use App\Support\Timetable;

/**
 * Lecturer details. Lecturer accounts are added by the super admin under
 * "User accounts"; heads of department assign them to subjects and sessions.
 */
class LecturerController extends Controller
{
    public function index()
    {
        $term = Term::current();

        $lecturers = User::where('role', 'lecturer')
            ->with('lecturedSubjects')
            ->withCount(['classSessions as term_sessions_count' => fn ($query) => $query->where('term_id', $term?->id)])
            ->orderBy('name')
            ->get();

        return view('lecturers.index', compact('lecturers', 'term'));
    }

    public function show(User $lecturer)
    {
        abort_unless($lecturer->hasRole('lecturer'), 404);

        $term = Term::current();
        $lecturer->load('lecturedSubjects.department');

        $sessions = $lecturer->classSessions()
            ->with(['subject', 'slots.classroom', 'slots.classSession.subject', 'slots.classSession.lecturers'])
            ->withCount('results')
            ->where('term_id', $term?->id)
            ->get()
            ->sortBy(fn ($session) => $session->subject->code.$session->name);

        $slots = $sessions->flatMap->slots;

        return view('lecturers.show', [
            'lecturer' => $lecturer,
            'term'     => $term,
            'sessions' => $sessions,
            'days'     => Timetable::byDay($slots),
            'hours'    => $slots->sum(fn ($slot) => Timetable::hours($slot)),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Department;
use App\Models\Term;
use App\Models\TimetableSlot;
use App\Models\User;
use App\Support\Timetable;
use Illuminate\Http\Request;

/**
 * Weekly timetable views: the whole timetable (with filters) and "My timetable"
 * for lecturers and students.
 */
class TimetableController extends Controller
{
    public function index(Request $request)
    {
        $term = $request->filled('term') ? Term::findOrFail($request->integer('term')) : Term::current();
        $filters = $request->only('department', 'lecturer', 'classroom');

        $slots = TimetableSlot::query()
            ->with(['classSession.subject', 'classSession.lecturers', 'classroom'])
            ->whereHas('classSession', function ($session) use ($term, $filters) {
                $session->where('term_id', $term?->id)
                    ->when($filters['department'] ?? null, fn ($q, $id) => $q->whereHas('subject', fn ($s) => $s->where('department_id', $id)))
                    ->when($filters['lecturer'] ?? null, fn ($q, $id) => $q->whereHas('lecturers', fn ($l) => $l->whereKey($id)));
            })
            ->when($filters['classroom'] ?? null, fn ($query, $id) => $query->where('classroom_id', $id))
            ->get();

        return view('timetable.index', [
            'term'        => $term,
            'terms'       => Term::orderByDesc('starts_on')->orderByDesc('id')->get(),
            'filters'     => $filters,
            'departments' => Department::orderBy('code')->get(),
            'lecturers'   => User::where('role', 'lecturer')->orderBy('name')->get(),
            'classrooms'  => Classroom::orderBy('code')->get(),
            'days'        => Timetable::byDay($slots),
            'slotCount'   => $slots->count(),
        ]);
    }

    /**
     * A lecturer's or student's own weekly timetable for the current term.
     */
    public function mine(Request $request)
    {
        $user = $request->user();
        abort_unless($user->can('teach') || $user->can('view-own-results'), 403);

        $term = Term::current();

        $slots = TimetableSlot::query()
            ->with(['classSession.subject', 'classSession.lecturers', 'classroom'])
            ->whereHas('classSession', function ($session) use ($term, $user) {
                $session->where('term_id', $term?->id);

                if ($user->can('teach')) {
                    $session->whereHas('lecturers', fn ($l) => $l->whereKey($user->id));
                } else {
                    $session->whereHas('results', fn ($r) => $r->where('student_id', $user->student?->id));
                }
            })
            ->get();

        return view('timetable.mine', [
            'term'  => $term,
            'days'  => Timetable::byDay($slots),
            'slots' => $slots->sortBy(fn ($slot) => $slot->day.$slot->starts_at),
        ]);
    }
}

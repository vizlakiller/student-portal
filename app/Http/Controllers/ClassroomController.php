<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Term;
use App\Support\Timetable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Classrooms: admin staff and the super admin add them; timetable users view them.
 */
class ClassroomController extends Controller
{
    public function index()
    {
        $term = Term::current();

        $classrooms = Classroom::withCount(['slots as term_slots_count' => fn ($query) => $query
                ->whereHas('classSession', fn ($session) => $session->where('term_id', $term?->id))])
            ->with(['slots' => fn ($query) => $query->whereHas('classSession', fn ($session) => $session->where('term_id', $term?->id))])
            ->orderBy('code')
            ->get();

        return view('classrooms.index', compact('classrooms', 'term'));
    }

    /** One room's weekly timetable. */
    public function show(Request $request, Classroom $classroom)
    {
        $term = $request->filled('term') ? Term::findOrFail($request->integer('term')) : Term::current();

        $slots = $classroom->slots()
            ->with(['classSession.subject', 'classSession.lecturers', 'classroom'])
            ->whereHas('classSession', fn ($query) => $query->where('term_id', $term?->id))
            ->get();

        return view('classrooms.show', [
            'classroom' => $classroom,
            'term'      => $term,
            'terms'     => Term::orderByDesc('starts_on')->get(),
            'days'      => Timetable::byDay($slots),
            'hours'     => $slots->sum(fn ($slot) => Timetable::hours($slot)),
        ]);
    }

    public function create()
    {
        return view('classrooms.create', ['classroom' => new Classroom(['type' => 'Lecture room'])]);
    }

    public function store(Request $request)
    {
        $classroom = Classroom::create($this->validateClassroom($request));

        return redirect()->route('classrooms.index')->with('success', "Classroom {$classroom->code} added.");
    }

    public function edit(Classroom $classroom)
    {
        return view('classrooms.edit', compact('classroom'));
    }

    public function update(Request $request, Classroom $classroom)
    {
        $classroom->update($this->validateClassroom($request, $classroom));

        return redirect()->route('classrooms.index')->with('success', "Classroom {$classroom->code} updated.");
    }

    public function destroy(Classroom $classroom)
    {
        if ($classroom->slots()->exists()) {
            return back()->with('error', "{$classroom->code} is used in a timetable, so it can't be deleted.");
        }

        $classroom->delete();

        return redirect()->route('classrooms.index')->with('success', "Classroom {$classroom->code} deleted.");
    }

    private function validateClassroom(Request $request, ?Classroom $classroom = null): array
    {
        return $request->validate([
            'code'     => ['required', 'string', 'max:20', Rule::unique('classrooms')->ignore($classroom)],
            'name'     => ['required', 'string', 'max:255'],
            'building' => ['nullable', 'string', 'max:255'],
            'type'     => ['required', Rule::in(Classroom::TYPES)],
            'capacity' => ['required', 'integer', 'between:1,2000'],
        ]);
    }
}

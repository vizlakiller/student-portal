<?php

namespace App\Http\Controllers;

use App\Models\ClassSession;
use App\Models\Classroom;
use App\Models\TimetableSlot;
use App\Support\Timetable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Heads of department: add or remove a session's weekly class times.
 * A class time is refused if the room, a lecturer or the session is already busy.
 */
class TimetableSlotController extends Controller
{
    public function store(Request $request, ClassSession $classSession)
    {
        Gate::authorize('manage-subject-timetable', $classSession->subject);

        $first = sprintf('%02d:00', Timetable::FIRST_HOUR);
        $last = sprintf('%02d:00', Timetable::LAST_HOUR);

        $data = $request->validate([
            'day'          => ['required', 'integer', Rule::in(array_keys(Timetable::DAYS))],
            'starts_at'    => ['required', 'date_format:H:i', "after_or_equal:{$first}"],
            'ends_at'      => ['required', 'date_format:H:i', 'after:starts_at', "before_or_equal:{$last}"],
            'classroom_id' => ['required', 'exists:classrooms,id'],
        ], [
            'starts_at.after_or_equal' => "Classes can start from {$first}.",
            'ends_at.before_or_equal'  => "Classes must end by {$last}.",
            'ends_at.after'            => 'The end time must be after the start time.',
        ], ['starts_at' => 'start time', 'ends_at' => 'end time', 'classroom_id' => 'classroom']);

        // Times are stored as HH:MM:SS so they compare correctly.
        $start = $data['starts_at'].':00';
        $end = $data['ends_at'].':00';

        $classSession->load('lecturers');
        $clashes = Timetable::clashesFor($classSession, (int) $data['day'], $start, $end, (int) $data['classroom_id']);

        if ($clashes) {
            return back()->withInput()->withErrors(['clash' => $clashes]);
        }

        $slot = $classSession->slots()->create([
            'day'          => $data['day'],
            'starts_at'    => $start,
            'ends_at'      => $end,
            'classroom_id' => $data['classroom_id'],
        ]);

        $message = "Class time added: {$slot->whenLabel()} in {$slot->classroom->label()}.";
        $room = Classroom::find($data['classroom_id']);
        if ($classSession->capacity && $room->capacity < $classSession->capacity) {
            $message .= " Note: the room holds {$room->capacity}, but the session allows {$classSession->capacity} students.";
        }

        return back()->with('success', $message);
    }

    public function destroy(TimetableSlot $slot)
    {
        Gate::authorize('manage-subject-timetable', $slot->classSession->subject);

        $slot->delete();

        return back()->with('success', "Class time removed: {$slot->whenLabel()}.");
    }
}

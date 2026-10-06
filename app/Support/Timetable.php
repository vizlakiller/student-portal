<?php

namespace App\Support;

use App\Models\ClassSession;
use App\Models\TimetableSlot;
use Illuminate\Support\Collection;

/**
 * Weekly timetable helpers: day names, clash checks and the weekly grid.
 */
class Timetable
{
    public const DAYS = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'];

    /** Hours shown on the weekly grid (classes must fit between them). */
    public const FIRST_HOUR = 8;

    public const LAST_HOUR = 20;

    /**
     * Problems that stop a class time being added, e.g.
     * "MK-201 Computer Lab A is already used by CSC1043 Group A on Monday 14:00 to 16:00."
     * Checks the room, the session's lecturers and the session itself, within the same term.
     *
     * @return array<int, string> empty when there is no clash
     */
    public static function clashesFor(ClassSession $session, int $day, string $start, string $end, int $classroomId, ?int $ignoreSlotId = null): array
    {
        $overlapping = TimetableSlot::query()
            ->with(['classSession.subject', 'classSession.lecturers', 'classroom'])
            ->where('day', $day)
            ->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start)
            ->whereHas('classSession', fn ($query) => $query->where('term_id', $session->term_id))
            ->when($ignoreSlotId, fn ($query) => $query->whereKeyNot($ignoreSlotId))
            ->get();

        $problems = [];

        foreach ($overlapping as $slot) {
            if ($slot->class_session_id === $session->id) {
                $problems[] = "This session already has a class at that time ({$slot->whenLabel()}).";
            } elseif ($slot->classroom_id === $classroomId) {
                $problems[] = "{$slot->classroom->label()} is already used by {$slot->classSession->label()} on {$slot->whenLabel()}.";
            }
        }

        $lecturers = self::lecturerClashes($session->lecturers->pluck('id'), $session->term_id, $day, $start, $end, $session->id);

        return array_values(array_unique([...$problems, ...$lecturers]));
    }

    /**
     * Lecturers (by id) who already teach another session at this time in the term.
     *
     * @return array<int, string> e.g. ["Puan Kavitha is already teaching MAT1023 Group A on Monday 10:00 to 12:00."]
     */
    public static function lecturerClashes(iterable $lecturerIds, int $termId, int $day, string $start, string $end, int $exceptSessionId): array
    {
        $lecturerIds = collect($lecturerIds);

        if ($lecturerIds->isEmpty()) {
            return [];
        }

        $slots = TimetableSlot::query()
            ->with(['classSession.subject', 'classSession.lecturers'])
            ->where('day', $day)
            ->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start)
            ->whereHas('classSession', fn ($query) => $query->where('term_id', $termId)->whereKeyNot($exceptSessionId))
            ->get();

        $problems = [];
        foreach ($slots as $slot) {
            foreach ($slot->classSession->lecturers->whereIn('id', $lecturerIds) as $lecturer) {
                $problems[] = "{$lecturer->name} is already teaching {$slot->classSession->label()} on {$slot->whenLabel()}.";
            }
        }

        return array_values(array_unique($problems));
    }

    /**
     * Do any two of these slots overlap? Used when registering a student in
     * several sessions. Returns a description of the first clash, or null.
     *
     * @param  Collection<int, TimetableSlot>  $slots  (with classSession.subject loaded)
     */
    public static function firstClashBetween(Collection $slots): ?string
    {
        $slots = $slots->values();

        for ($i = 0; $i < $slots->count(); $i++) {
            for ($j = $i + 1; $j < $slots->count(); $j++) {
                $a = $slots[$i];
                $b = $slots[$j];

                if ($a->class_session_id !== $b->class_session_id
                    && $a->day === $b->day && $a->starts_at < $b->ends_at && $a->ends_at > $b->starts_at) {
                    return "{$a->classSession->label()} and {$b->classSession->label()} are both on {$a->dayName()} around ".substr(max($a->starts_at, $b->starts_at), 0, 5).'.';
                }
            }
        }

        return null;
    }

    /**
     * Arrange slots for the weekly grid:
     * [day => [['slot' => TimetableSlot, 'lane' => 0, 'lanes' => 2], ...]].
     * Classes at the same time sit side by side ("lanes") instead of on top of each other.
     *
     * @param  Collection<int, TimetableSlot>  $slots
     */
    public static function byDay(Collection $slots): array
    {
        $days = array_fill_keys(array_keys(self::DAYS), []);

        foreach ($slots->sortBy('starts_at') as $slot) {
            $days[$slot->day][] = $slot;
        }

        // Hide Saturday when nothing happens on it
        if (empty($days[6])) {
            unset($days[6]);
        }

        return array_map(fn ($daySlots) => self::lanes($daySlots), $days);
    }

    /**
     * Give each slot a lane so overlapping classes don't cover each other.
     * Slots must be sorted by start time.
     */
    private static function lanes(array $slots): array
    {
        $placed = [];
        $group = [];        // slots that overlap in a chain share the same number of lanes
        $laneEnds = [];
        $groupEnd = null;

        $finishGroup = function () use (&$placed, &$group, &$laneEnds) {
            foreach ($group as $item) {
                $placed[] = $item + ['lanes' => count($laneEnds)];
            }
            $group = [];
            $laneEnds = [];
        };

        foreach ($slots as $slot) {
            if ($groupEnd !== null && $slot->starts_at >= $groupEnd) {
                $finishGroup();
                $groupEnd = null;
            }

            $lane = 0;
            while (isset($laneEnds[$lane]) && $laneEnds[$lane] > $slot->starts_at) {
                $lane++;
            }

            $laneEnds[$lane] = $slot->ends_at;
            $group[] = ['slot' => $slot, 'lane' => $lane];
            $groupEnd = $groupEnd === null ? $slot->ends_at : max($groupEnd, $slot->ends_at);
        }

        $finishGroup();

        return $placed;
    }

    /** Position of a slot on the grid, as CSS percentages of the day's height. */
    public static function position(TimetableSlot $slot): array
    {
        $span = (self::LAST_HOUR - self::FIRST_HOUR) * 60;
        $start = self::minutes($slot->starts_at) - self::FIRST_HOUR * 60;
        $length = self::minutes($slot->ends_at) - self::minutes($slot->starts_at);

        return [
            'top' => round(max(0, $start) / $span * 100, 3),
            'height' => round($length / $span * 100, 3),
        ];
    }

    public static function minutes(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return $hours * 60 + $minutes;
    }

    /** Length of a slot in hours, e.g. 2.0 */
    public static function hours(TimetableSlot $slot): float
    {
        return (self::minutes($slot->ends_at) - self::minutes($slot->starts_at)) / 60;
    }
}

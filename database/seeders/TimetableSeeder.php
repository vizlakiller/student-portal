<?php

namespace Database\Seeders;

use App\Models\ClassSession;
use App\Models\Classroom;
use App\Models\Result;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * A clash-free weekly timetable: sessions, their lecturers and class times.
 * Students waiting for marks are put into the sessions of the current term.
 */
class TimetableSeeder extends Seeder
{
    /**
     * [subject, session, lecturer emails, capacity, [[day, start, end, room], ...]]
     * Days: 1 = Monday ... 5 = Friday. No Friday afternoon classes (Friday prayers).
     */
    private const PLAN = [
        ['CSC1013', 'Group A', ['lecturer@example.com', 'lecturer4@example.com'], 40, [[1, '08:00', '10:00', 'BK1-101'], [4, '08:00', '10:00', 'MK-201']]],
        ['MAT1023', 'Group A', ['lecturer2@example.com'], 40, [[1, '10:00', '12:00', 'BK1-102'], [3, '10:00', '12:00', 'BK1-102']]],
        ['ITC1033', 'Group A', ['lecturer3@example.com'], 40, [[2, '08:00', '10:00', 'BK1-102'], [4, '14:00', '16:00', 'MK-202']]],
        ['ENG1012', 'Group A', ['lecturer3@example.com'], 25, [[2, '14:00', '16:00', 'BK2-110'], [5, '10:30', '12:30', 'BK2-110']]],
        ['CSC1043', 'Group A', ['lecturer@example.com'], 25, [[1, '14:00', '16:00', 'MK-201'], [4, '10:00', '12:00', 'BK1-102']]],
        ['CSC1043', 'Group B', ['lecturer4@example.com'], 25, [[2, '10:00', '12:00', 'MK-201'], [4, '14:00', '16:00', 'BK1-102']]],
        ['DBS1053', 'Group A', ['lecturer2@example.com'], 35, [[3, '08:00', '10:00', 'MK-202'], [5, '08:30', '10:30', 'MK-202']]],
        ['NET1063', 'Group A', ['lecturer2@example.com'], 35, [[2, '14:00', '16:00', 'MK-202'], [3, '14:00', '16:00', 'MK-202']]],
        ['WEB2013', 'Group A', ['lecturer@example.com'], 35, [[2, '10:00', '12:00', 'MK-202'], [5, '08:30', '10:30', 'MK-201']]],
        ['SEN2023', 'Group A', ['lecturer@example.com'], 40, [[1, '10:00', '12:00', 'BK1-101'], [3, '08:00', '10:00', 'BK1-101']]],
        ['MPU2032', 'Group A', ['lecturer3@example.com'], 40, [[3, '10:00', '12:00', 'BK2-110']]],
        ['PRJ2044', 'Group A', ['lecturer@example.com'], 35, [[3, '14:00', '17:00', 'MK-201']]],
    ];

    public function run(): void
    {
        $current = Term::current();
        if (! $current) {
            return;
        }

        $this->buildTerm($current);

        // The previous term gets the same timetable (without students), to show past terms.
        $previous = Term::where('name', 'March 2026')->first();
        if ($previous && ! $previous->sessions()->exists()) {
            $this->buildTerm($previous);
        }

        $this->placeWaitingStudents($current);
    }

    private function buildTerm(Term $term): void
    {
        $rooms = Classroom::pluck('id', 'code');

        foreach (self::PLAN as [$code, $name, $emails, $capacity, $times]) {
            $subject = Subject::with('lecturers')->where('code', $code)->first();
            if (! $subject) {
                continue;
            }

            // Use the planned lecturers if they teach the subject; otherwise the subject's own
            // lecturers (e.g. on a system upgraded from version 2).
            $planned = User::whereIn('email', $emails)->pluck('id');
            $usePlan = $planned->isNotEmpty() && $planned->diff($subject->lecturers->pluck('id'))->isEmpty();

            if (! $usePlan && $name !== 'Group A') {
                continue;   // extra groups only when their lecturer exists, to keep the timetable clash-free
            }

            $session = ClassSession::firstOrCreate(
                ['term_id' => $term->id, 'subject_id' => $subject->id, 'name' => $name],
                ['capacity' => $capacity],
            );

            if (! $session->lecturers()->exists()) {
                $session->lecturers()->sync($usePlan ? $planned : $subject->lecturers->pluck('id'));
            }

            if (! $session->slots()->exists()) {
                foreach ($times as [$day, $start, $end, $room]) {
                    $session->slots()->create([
                        'day' => $day, 'starts_at' => "{$start}:00", 'ends_at' => "{$end}:00", 'classroom_id' => $rooms[$room],
                    ]);
                }
            }
        }
    }

    /**
     * Students registered without a session (waiting for marks) go into this
     * term's sessions of that subject, shared out evenly between groups.
     */
    private function placeWaitingStudents(Term $term): void
    {
        $sessions = ClassSession::where('term_id', $term->id)->orderBy('name')->get()->groupBy('subject_id');

        $waiting = Result::whereNull('marks')->whereNull('class_session_id')->orderBy('student_id')->get()->groupBy('subject_id');

        foreach ($waiting as $subjectId => $results) {
            $groups = $sessions[$subjectId] ?? collect();
            if ($groups->isEmpty()) {
                continue;
            }

            foreach ($results->values() as $i => $result) {
                $result->update(['class_session_id' => $groups[$i % $groups->count()]->id]);
            }
        }
    }
}

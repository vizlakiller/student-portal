<?php

namespace Database\Seeders;

use App\Models\Result;
use App\Models\ResultChangeRequest;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * One mark change waiting for the lecturer, and one already approved.
 */
class MarkChangeSeeder extends Seeder
{
    public function run(): void
    {
        if (ResultChangeRequest::exists()) {
            return;
        }

        $hodId = User::where('email', 'hod@example.com')->value('id');
        $lecturerId = User::where('email', 'lecturer@example.com')->value('id')
            ?? User::where('role', 'lecturer')->value('id');
        $csc1013 = Subject::where('code', 'CSC1013')->value('id');

        $pending = $this->resultFor('DCS2024003', $csc1013);
        if ($pending) {
            $pending->changeRequests()->create([
                'old_marks'    => $pending->marks,
                'new_marks'    => min(100, $pending->marks + 6),
                'reason'       => 'Re-marking after appeal: question 4 of the final exam was not counted.',
                'requested_by' => $hodId,
            ]);
        }

        $approved = $this->resultFor('DCS2024002', $csc1013);
        if ($approved) {
            $old = $approved->marks;
            $approved->update(['marks' => min(100, $old + 4)]);
            $approved->changeRequests()->create([
                'old_marks'     => $old,
                'new_marks'     => $approved->marks,
                'reason'        => 'Assignment 2 marks were entered late.',
                'requested_by'  => $hodId,
                'status'        => 'approved',
                'decided_by'    => $lecturerId,
                'decided_at'    => now()->subDays(3),
                'decision_note' => 'Checked against the assignment records.',
            ]);
        }
    }

    private function resultFor(string $studentNo, ?int $subjectId): ?Result
    {
        $student = Student::where('student_no', $studentNo)->first();

        return $student?->results()->where('subject_id', $subjectId)->whereNotNull('marks')->first();
    }
}

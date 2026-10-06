<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Registrar: register a student for subjects. Marks stay empty until the
 * subject's teacher enters them.
 */
class RegistrationController extends Controller
{
    public function create(Student $student)
    {
        $subjects = Subject::with('teacher')
            ->whereNotIn('id', $student->results()->pluck('subject_id'))
            ->orderBy('code')
            ->get();

        if ($subjects->isEmpty()) {
            return redirect()
                ->route('students.show', $student)
                ->with('error', 'This student is already registered for every subject. Add a new subject first.');
        }

        return view('registrations.create', compact('student', 'subjects'));
    }

    public function store(Request $request, Student $student)
    {
        $data = $request->validate([
            'subject_ids'   => ['required', 'array', 'min:1'],
            'subject_ids.*' => [
                'integer',
                'exists:subjects,id',
                Rule::unique('results', 'subject_id')->where('student_id', $student->id),
            ],
            'semester'      => ['required', 'integer', 'between:1,12'],
        ], [
            'subject_ids.required'   => 'Tick at least one subject.',
            'subject_ids.*.unique'   => 'The student is already registered for one of those subjects.',
        ]);

        DB::transaction(function () use ($student, $data) {
            foreach ($data['subject_ids'] as $subjectId) {
                $student->results()->create(['subject_id' => $subjectId, 'semester' => $data['semester']]);
            }
        });

        $count = count($data['subject_ids']);

        return redirect()
            ->route('students.show', $student)
            ->with('success', "Registered for {$count} ".str('subject')->plural($count).'. Their teachers can now enter marks.');
    }
}

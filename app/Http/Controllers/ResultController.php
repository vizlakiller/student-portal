<?php

namespace App\Http\Controllers;

use App\Models\Result;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ResultController extends Controller
{
    public function create(Student $student)
    {
        // Only subjects this student has no result for yet
        $subjects = Subject::whereNotIn('id', $student->results()->pluck('subject_id'))
            ->orderBy('code')
            ->get();

        if ($subjects->isEmpty()) {
            return redirect()
                ->route('students.show', $student)
                ->with('error', 'This student already has a result for every subject. Add a new subject first.');
        }

        return view('results.create', [
            'student'  => $student,
            'subjects' => $subjects,
            'result'   => new Result(['semester' => $student->semester]),
        ]);
    }

    public function store(Request $request, Student $student)
    {
        $data = $request->validate([
            'subject_id' => [
                'required',
                'exists:subjects,id',
                Rule::unique('results')->where('student_id', $student->id),
            ],
            'semester' => ['required', 'integer', 'between:1,12'],
            'marks'    => ['required', 'integer', 'between:0,100'],
        ], [
            'subject_id.unique' => 'This student already has a result for that subject.',
        ], [
            'subject_id' => 'subject',
        ]);

        $result = $student->results()->create($data);

        return redirect()
            ->route('students.show', $student)
            ->with('success', "Result saved: {$result->subject->code}, grade {$result->grade}.");
    }

    public function edit(Student $student, Result $result)
    {
        return view('results.edit', compact('student', 'result'));
    }

    public function update(Request $request, Student $student, Result $result)
    {
        $result->update($request->validate([
            'semester' => ['required', 'integer', 'between:1,12'],
            'marks'    => ['required', 'integer', 'between:0,100'],
        ]));

        return redirect()
            ->route('students.show', $student)
            ->with('success', "Result updated: {$result->subject->code}, grade {$result->grade}.");
    }

    public function destroy(Student $student, Result $result)
    {
        $result->delete();

        return redirect()
            ->route('students.show', $student)
            ->with('success', "Result for {$result->subject->code} deleted.");
    }
}

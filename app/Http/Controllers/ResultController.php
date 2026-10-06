<?php

namespace App\Http\Controllers;

use App\Models\Result;
use App\Models\Student;
use Illuminate\Http\Request;

/**
 * Super admin: change any mark directly.
 * Registrar: remove a subject registration that has no marks yet.
 */
class ResultController extends Controller
{
    public function edit(Student $student, Result $result)
    {
        return view('results.edit', compact('student', 'result'));
    }

    public function update(Request $request, Student $student, Result $result)
    {
        $result->update($request->validate([
            'semester' => ['required', 'integer', 'between:1,12'],
            'marks'    => ['nullable', 'integer', 'between:0,100'],
        ]));

        $grade = $result->grade ? "grade {$result->grade}" : 'no marks yet';

        return redirect()
            ->route('students.show', $student)
            ->with('success', "Result updated: {$result->subject->code}, {$grade}.");
    }

    public function destroy(Request $request, Student $student, Result $result)
    {
        if ($result->isMarked() && ! $request->user()->can('edit-marks-directly')) {
            return back()->with('error', "{$result->subject->code} already has marks, so only the super admin can remove it.");
        }

        $result->delete();

        return redirect()
            ->route('students.show', $student)
            ->with('success', "{$result->subject->code} removed from this student.");
    }
}

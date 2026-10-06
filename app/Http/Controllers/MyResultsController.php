<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Students: their own subjects, results and transcript (read-only).
 */
class MyResultsController extends Controller
{
    public function index(Request $request)
    {
        $student = $request->user()->student;
        abort_unless($student, 404, 'Your login is not linked to a student record. Please contact the registrar.');

        return view('my.results', StudentController::recordData($student));
    }

    public function transcript(Request $request)
    {
        $student = $request->user()->student;
        abort_unless($student, 404);

        return view('students.transcript', StudentController::recordData($student));
    }
}

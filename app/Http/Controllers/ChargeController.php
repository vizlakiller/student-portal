<?php

namespace App\Http\Controllers;

use App\Models\Charge;
use App\Models\Student;
use Illuminate\Http\Request;

/**
 * Accountant: bill one student for a fee (tuition, hostel, exam...).
 */
class ChargeController extends Controller
{
    public function create(Student $student)
    {
        return view('finance.charge-create', compact('student'));
    }

    public function store(Request $request, Student $student)
    {
        $data = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'amount'      => ['required', 'decimal:0,2', 'min:0.01', 'max:1000000'],
            'semester'    => ['nullable', 'integer', 'between:1,12'],
            'due_date'    => ['nullable', 'date'],
        ]);

        $student->charges()->create([...$data, 'created_by' => $request->user()->id]);

        return redirect()
            ->route('finance.students.show', $student)
            ->with('success', "Charge added: {$data['description']}.");
    }

    public function destroy(Charge $charge)
    {
        $student = $charge->student;
        $charge->delete();

        return redirect()
            ->route('finance.students.show', $student)
            ->with('success', "Charge removed: {$charge->description}.");
    }
}

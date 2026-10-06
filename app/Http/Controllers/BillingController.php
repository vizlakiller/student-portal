<?php

namespace App\Http\Controllers;

use App\Models\Programme;
use App\Models\Student;
use App\Support\Branding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Accountant: bill the same fee to every student in a programme at once,
 * e.g. "Semester 2 tuition fee" for all active DCS students.
 */
class BillingController extends Controller
{
    public function create()
    {
        return view('finance.billing', ['programmes' => Programme::withCount('students')->orderBy('code')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'programme_id' => ['nullable', 'exists:programmes,id'],
            'semester'     => ['nullable', 'integer', 'between:1,12'],
            'description'  => ['required', 'string', 'max:255'],
            'amount'       => ['required', 'decimal:0,2', 'min:0.01', 'max:1000000'],
            'due_date'     => ['nullable', 'date'],
        ], [], [
            'programme_id' => 'programme',
            'semester'     => 'current semester',
        ]);

        $students = Student::where('status', 'Active')
            ->when($data['programme_id'] ?? null, fn ($query, $id) => $query->where('programme_id', $id))
            ->when($data['semester'] ?? null, fn ($query, $semester) => $query->where('semester', $semester))
            ->get();

        $billed = 0;
        $skipped = 0;

        DB::transaction(function () use ($students, $data, $request, &$billed, &$skipped) {
            foreach ($students as $student) {
                // Don't bill the same fee twice if the accountant submits again.
                if ($student->charges()->where('description', $data['description'])->exists()) {
                    $skipped++;

                    continue;
                }

                $student->charges()->create([
                    'description' => $data['description'],
                    'amount'      => $data['amount'],
                    'semester'    => $data['semester'] ?? $student->semester,
                    'due_date'    => $data['due_date'] ?? null,
                    'created_by'  => $request->user()->id,
                ]);
                $billed++;
            }
        });

        $message = "Billed {$billed} ".str('student')->plural($billed).' '.Branding::money($data['amount']).' each.';
        if ($skipped) {
            $message .= " {$skipped} already had this charge and were skipped.";
        }

        return redirect()->route('finance.index')->with($billed ? 'success' : 'error', $billed ? $message : 'No students were billed. '.($skipped ? "All {$skipped} matching students already have this charge." : 'No active students match those choices.'));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Programme;
use App\Models\Student;
use App\Support\Grading;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only('search', 'programme', 'status');

        $students = Student::with(['programme', 'results.subject'])
            ->filter($filters)
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();   // keep the search/filters when changing page

        $programmes = Programme::orderBy('code')->get();

        return view('students.index', compact('students', 'programmes', 'filters'));
    }

    public function create()
    {
        $student = new Student([
            'intake_year' => now()->year,
            'semester'    => 1,
            'status'      => 'Active',
        ]);

        return view('students.create', [
            'student'    => $student,
            'programmes' => Programme::orderBy('code')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $student = Student::create($this->validateStudent($request));

        return redirect()
            ->route('students.show', $student)
            ->with('success', "Student {$student->name} added.");
    }

    public function show(Student $student)
    {
        return view('students.show', $this->recordData($student));
    }

    public function transcript(Student $student)
    {
        return view('students.transcript', $this->recordData($student));
    }

    public function edit(Student $student)
    {
        return view('students.edit', [
            'student'    => $student,
            'programmes' => Programme::orderBy('code')->get(),
        ]);
    }

    public function update(Request $request, Student $student)
    {
        $student->update($this->validateStudent($request, $student));

        return redirect()
            ->route('students.show', $student)
            ->with('success', 'Student details updated.');
    }

    public function destroy(Student $student)
    {
        $student->delete();   // their results are deleted too (cascade)

        return redirect()
            ->route('students.index')
            ->with('success', "Student {$student->name} deleted.");
    }

    /**
     * Download the (filtered) student list as a CSV file that opens in Excel.
     */
    public function export(Request $request)
    {
        $students = Student::with('programme')
            ->filter($request->only('search', 'programme', 'status'))
            ->orderBy('name')
            ->get();

        $filename = 'students-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($students) {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF");   // lets Excel read the file as UTF-8

            fputcsv($file, ['Student No', 'Name', 'Email', 'Phone', 'Gender', 'Date of Birth',
                'Programme', 'Intake Year', 'Semester', 'Status']);

            foreach ($students as $student) {
                fputcsv($file, [
                    $student->student_no,
                    $student->name,
                    $student->email,
                    $student->phone,
                    $student->gender,
                    $student->date_of_birth?->format('Y-m-d'),
                    $student->programme->code,
                    $student->intake_year,
                    $student->semester,
                    $student->status,
                ]);
            }

            fclose($file);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Data shared by the profile page and the printable transcript:
     * results grouped by semester, with each semester's GPA and the CGPA.
     */
    private function recordData(Student $student): array
    {
        $student->load(['programme', 'results.subject']);

        $semesters = $student->results
            ->sortBy('subject.code')
            ->groupBy('semester')
            ->sortKeys()
            ->map(fn ($results) => [
                'results' => $results,
                'credits' => $results->sum('subject.credit_hours'),
                'gpa'     => Grading::gpa($results),
            ]);

        return [
            'student'   => $student,
            'semesters' => $semesters,
            'cgpa'      => $student->cgpa(),
            'credits'   => $student->results->sum('subject.credit_hours'),
        ];
    }

    private function validateStudent(Request $request, ?Student $student = null): array
    {
        return $request->validate([
            'student_no'    => ['required', 'string', 'max:20', Rule::unique('students')->ignore($student)],
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'email', 'max:255', Rule::unique('students')->ignore($student)],
            'phone'         => ['nullable', 'string', 'max:20'],
            'gender'        => ['required', Rule::in(Student::GENDERS)],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'address'       => ['nullable', 'string', 'max:500'],
            'programme_id'  => ['required', 'exists:programmes,id'],
            'intake_year'   => ['required', 'integer', 'between:2000,'.(now()->year + 1)],
            'semester'      => ['required', 'integer', 'between:1,12'],
            'status'        => ['required', Rule::in(Student::STATUSES)],
        ], [], [
            'student_no'   => 'student number',
            'programme_id' => 'programme',
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Programme;
use App\Models\Student;
use App\Models\User;
use App\Support\Grading;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only('search', 'programme', 'status');

        $students = Student::with('programme')
            ->when($request->user()->can('view-results'), fn ($query) => $query->with('results.subject'))
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

    /**
     * Adds the student and creates their login: email = student email,
     * first password = student number (they must change it at first login).
     */
    public function store(Request $request)
    {
        $data = $request->validate($this->rules($request, null, true), [], $this->attributeNames());

        $student = DB::transaction(function () use ($data) {
            $student = Student::create($data);
            $this->createLogin($student);

            return $student;
        });

        return redirect()
            ->route('students.show', $student)
            ->with('success', "Student {$student->name} added. They can log in with {$student->email} and their student number as the first password.");
    }

    public function show(Request $request, Student $student)
    {
        $student->load(['programme', 'user']);

        // Results are only loaded for roles allowed to see them.
        $record = $request->user()->can('view-results') ? $this->recordData($student) : null;

        return view('students.show', compact('student', 'record'));
    }

    public function transcript(Request $request, Student $student)
    {
        Gate::authorize('view-student-results', $student);

        return view('students.transcript', $this->recordData($student));
    }

    public function edit(Request $request, Student $student)
    {
        return view('students.edit', [
            'student'    => $student,
            'programmes' => Programme::orderBy('code')->get(),
        ]);
    }

    public function update(Request $request, Student $student)
    {
        // Admin staff only send (and may only change) contact details;
        // the registrar may also change enrolment details.
        $data = $request->validate($this->rules($request, $student), [], $this->attributeNames());

        DB::transaction(function () use ($student, $data) {
            $student->update($data);

            // Keep the student's login in step with their record.
            $student->user?->update(['name' => $student->name, 'email' => $student->email]);
        });

        return redirect()
            ->route('students.show', $student)
            ->with('success', 'Student details updated.');
    }

    public function destroy(Student $student)
    {
        DB::transaction(function () use ($student) {
            $user = $student->user;
            $student->delete();   // their results, charges and payments are deleted too (cascade)
            $user?->delete();
        });

        return redirect()
            ->route('students.index')
            ->with('success', "Student {$student->name} deleted.");
    }

    /**
     * Student forgot their password: set it back to their student number.
     */
    public function resetPassword(Student $student)
    {
        if ($student->user) {
            $student->user->update(['password' => $student->student_no, 'must_change_password' => true]);
        } else {
            $this->createLogin($student);
        }

        return back()->with('success', "Password reset. {$student->name} can log in with their student number and will be asked to choose a new password.");
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
     * Results grouped by semester, with each semester's GPA and the CGPA.
     * Shared by the student page, the transcript and the student's own page.
     */
    public static function recordData(Student $student): array
    {
        $student->load(['programme', 'results.subject.lecturers', 'results.classSession.lecturers', 'results.classSession.term', 'results.pendingChange']);

        $semesters = $student->results
            ->sortBy('subject.code')
            ->groupBy('semester')
            ->sortKeys()
            ->map(fn ($results) => [
                'results' => $results,
                'credits' => $results->filter->isMarked()->sum('subject.credit_hours'),
                'gpa'     => Grading::gpa($results),
            ]);

        return [
            'student'   => $student,
            'semesters' => $semesters,
            'cgpa'      => $student->cgpa(),
            'credits'   => $student->results->filter->isMarked()->sum('subject.credit_hours'),
        ];
    }

    private function createLogin(Student $student): void
    {
        $user = User::create([
            'name'                 => $student->name,
            'email'                => $student->email,
            'password'             => $student->student_no,
            'role'                 => 'student',
            'must_change_password' => true,
        ]);

        $student->user()->associate($user)->save();
    }

    /**
     * Validation rules for the fields this user is allowed to change.
     * A new student always needs the enrolment fields.
     */
    private function rules(Request $request, ?Student $student = null, bool $creating = false): array
    {
        $rules = [
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'email', 'max:255',
                Rule::unique('students')->ignore($student),
                Rule::unique('users')->ignore($student?->user_id),   // it is also their login
            ],
            'phone'         => ['nullable', 'string', 'max:20'],
            'gender'        => ['required', Rule::in(Student::GENDERS)],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'address'       => ['nullable', 'string', 'max:500'],
        ];

        if ($creating || $request->user()->can('edit-student-enrolment')) {
            $rules += [
                'student_no'   => ['required', 'string', 'max:20', Rule::unique('students')->ignore($student)],
                'programme_id' => ['required', 'exists:programmes,id'],
                'intake_year'  => ['required', 'integer', 'between:2000,'.(now()->year + 1)],
                'semester'     => ['required', 'integer', 'between:1,12'],
                'status'       => ['required', Rule::in(Student::STATUSES)],
            ];
        }

        return $rules;
    }

    private function attributeNames(): array
    {
        return [
            'student_no'   => 'student number',
            'programme_id' => 'programme',
        ];
    }
}

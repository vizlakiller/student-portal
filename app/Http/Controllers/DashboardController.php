<?php

namespace App\Http\Controllers;

use App\Models\Programme;
use App\Models\Result;
use App\Models\Student;
use App\Models\Subject;
use App\Support\Grading;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $stats = [
            'students'   => Student::count(),
            'active'     => Student::where('status', 'Active')->count(),
            'programmes' => Programme::count(),
            'subjects'   => Subject::count(),
        ];

        $programmes = Programme::withCount('students')->orderByDesc('students_count')->get();

        // e.g. ['Active' => 28, 'Deferred' => 2, ...] in the order of Student::STATUSES
        $counts = Student::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $statuses = collect(Student::STATUSES)->mapWithKeys(fn ($status) => [$status => $counts[$status] ?? 0]);

        // How many results got each grade: ['A' => 20, 'A-' => 15, ...]
        $grades = array_fill_keys(Grading::grades(), 0);
        foreach (Result::pluck('marks') as $marks) {
            $grades[Grading::forMarks($marks)[0]]++;
        }

        // Top five students by CGPA. Fine for a few hundred students;
        // a large college would store the CGPA in a column instead.
        $topStudents = Student::with(['programme', 'results.subject'])
            ->has('results')
            ->get()
            ->sortByDesc(fn (Student $student) => $student->cgpa())
            ->take(5);

        $recentStudents = Student::with('programme')->latest()->take(5)->get();

        return view('dashboard', compact('stats', 'programmes', 'statuses', 'grades', 'topStudents', 'recentStudents'));
    }
}

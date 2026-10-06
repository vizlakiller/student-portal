<?php

namespace App\Http\Controllers;

use App\Models\Programme;
use App\Models\Result;
use App\Models\ResultChangeRequest;
use App\Models\Student;
use App\Models\Subject;
use App\Support\Grading;
use Illuminate\Http\Request;

/**
 * Everyone lands here after logging in. Each role sees the page it needs most.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        if ($user->hasRole('student')) {
            return redirect()->route('my.results');
        }

        if ($user->hasRole('accountant')) {
            return redirect()->route('finance.index');
        }

        if ($user->hasRole('management')) {
            return redirect()->route('statistics');
        }

        if ($user->hasRole('lecturer')) {
            return $this->lecturerDashboard($request);
        }

        return $this->generalDashboard($request);
    }

    private function lecturerDashboard(Request $request)
    {
        $term = \App\Models\Term::current();

        $sessions = $request->user()->classSessions()
            ->with(['subject', 'lecturers', 'slots.classroom'])
            ->withCount([
                'results',
                'results as marked_count' => fn ($query) => $query->whereNotNull('marks'),
            ])
            ->where('term_id', $term?->id)
            ->get()
            ->sortBy(fn ($session) => $session->subject->code.$session->name);

        $pending = ResultChangeRequest::visibleTo($request->user())
            ->where('status', 'pending')
            ->with(['result.student', 'result.subject.lecturers', 'result.classSession.lecturers', 'requester'])
            ->latest()
            ->get();

        return view('dashboards.lecturer', compact('sessions', 'pending', 'term'));
    }

    private function generalDashboard(Request $request)
    {
        $canSeeResults = $request->user()->can('view-results');

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

        $grades = null;
        $topStudents = collect();

        if ($canSeeResults) {
            // How many results got each grade: ['A' => 20, 'A-' => 15, ...]
            $grades = array_fill_keys(Grading::grades(), 0);
            foreach (Result::whereNotNull('marks')->pluck('marks') as $marks) {
                $grades[Grading::forMarks($marks)[0]]++;
            }

            // Top five students by CGPA. Fine for a few hundred students;
            // a large college would store the CGPA in a column instead.
            $topStudents = Student::with(['programme', 'results.subject'])
                ->whereHas('results', fn ($query) => $query->whereNotNull('marks'))
                ->get()
                ->sortByDesc(fn (Student $student) => $student->cgpa())
                ->take(5);
        }

        $recentStudents = Student::with('programme')->latest()->take(5)->get();

        return view('dashboard', compact('stats', 'programmes', 'statuses', 'grades', 'topStudents', 'recentStudents', 'canSeeResults'));
    }
}

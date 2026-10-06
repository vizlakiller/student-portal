<?php

namespace App\Http\Controllers;

use App\Models\Charge;
use App\Models\Classroom;
use App\Models\ClassSession;
use App\Models\Department;
use App\Models\Payment;
use App\Models\Programme;
use App\Models\Result;
use App\Models\Student;
use App\Models\Term;
use App\Models\TimetableSlot;
use App\Support\Grading;
use App\Support\Timetable;

/**
 * Management (Director / COO / CEO) and super admin: the whole picture on one
 * page. Everything here is read-only.
 *
 * The figures are worked out in PHP, which is fine for a few thousand
 * students. A much larger college would pre-calculate them overnight.
 */
class StatisticsController extends Controller
{
    public function __invoke()
    {
        $term = Term::current();

        return view('statistics.index', [
            'term'      => $term,
            'students'  => $this->studentFigures(),
            'register'  => $this->registrationFigures($term),
            'results'   => $this->resultFigures(),
            'finance'   => $this->financeFigures(),
            'timetable' => $this->timetableFigures($term),
        ]);
    }

    private function studentFigures(): array
    {
        $byProgramme = Programme::withCount([
            'students',
            'students as active_count' => fn ($query) => $query->where('status', 'Active'),
        ])->orderByDesc('students_count')->get();

        $statusCounts = Student::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return [
            'total'       => Student::count(),
            'active'      => Student::where('status', 'Active')->count(),
            'new_intake'  => Student::where('intake_year', now()->year)->count(),
            'graduated'   => $statusCounts['Graduated'] ?? 0,
            'byProgramme' => $byProgramme,
            'statuses'    => collect(Student::STATUSES)->mapWithKeys(fn ($status) => [$status => $statusCounts[$status] ?? 0]),
        ];
    }

    private function registrationFigures(?Term $term): array
    {
        $registrations = Result::with('subject')
            ->whereHas('classSession', fn ($query) => $query->where('term_id', $term?->id))
            ->get();

        $departments = Department::orderBy('code')->get()->keyBy('id');

        $byDepartment = $registrations
            ->groupBy(fn ($result) => $result->subject->department_id ?? 0)
            ->map(fn ($group, $departmentId) => [
                'label'  => $departments[$departmentId]->code ?? 'No department',
                'name'   => $departments[$departmentId]->name ?? 'Subjects without a department',
                'count'  => $group->count(),
                'marked' => $group->whereNotNull('marks')->count(),
            ])
            ->sortByDesc('count')
            ->values();

        return [
            'count'        => $registrations->count(),
            'students'     => $registrations->pluck('student_id')->unique()->count(),
            'marked'       => $registrations->whereNotNull('marks')->count(),
            'byDepartment' => $byDepartment,
        ];
    }

    private function resultFigures(): array
    {
        $marked = Result::whereNotNull('marks')->pluck('marks');
        $passPoint = config('grading.pass_point');

        $grades = array_fill_keys(Grading::grades(), 0);
        $passed = 0;
        foreach ($marked as $marks) {
            [$grade, $point] = Grading::forMarks($marks);
            $grades[$grade]++;
            $passed += $point >= $passPoint ? 1 : 0;
        }

        // Average CGPA per programme (students with at least one mark)
        $students = Student::with(['programme', 'results.subject'])
            ->whereHas('results', fn ($query) => $query->whereNotNull('marks'))
            ->get();

        $cgpas = $students->mapWithKeys(fn ($student) => [$student->id => $student->cgpa()]);

        $byProgramme = $students->groupBy('programme_id')
            ->map(fn ($group) => [
                'label'    => $group->first()->programme->code,
                'name'     => $group->first()->programme->name,
                'average'  => round($group->avg(fn ($student) => $cgpas[$student->id]), 2),
                'students' => $group->count(),
            ])
            ->sortByDesc('average')
            ->values();

        return [
            'marked'      => $marked->count(),
            'passRate'    => $marked->count() ? round($passed / $marked->count() * 100, 1) : null,
            'averageCgpa' => $cgpas->filter()->isNotEmpty() ? round($cgpas->filter()->avg(), 2) : null,
            'firstClass'  => $cgpas->filter(fn ($cgpa) => $cgpa >= 3.67)->count(),
            'grades'      => $grades,
            'byProgramme' => $byProgramme,
        ];
    }

    private function financeFigures(): array
    {
        $students = Student::with('programme')->withBalance()->get();
        $owing = $students->filter(fn ($student) => $student->balance() > 0);

        $billed = (float) Charge::sum('amount');
        $collected = (float) Payment::sum('amount');

        // Money received in each of the last six months
        $since = now()->startOfMonth()->subMonths(5);
        $received = Payment::where('paid_at', '>=', $since)->get(['amount', 'paid_at'])
            ->groupBy(fn ($payment) => $payment->paid_at->format('Y-m'))
            ->map->sum('amount');

        $months = collect(range(0, 5))->map(function ($i) use ($since, $received) {
            $month = $since->copy()->addMonths($i);

            return ['label' => $month->format('M'), 'title' => $month->format('F Y'), 'amount' => (float) ($received[$month->format('Y-m')] ?? 0)];
        });

        $outstandingByProgramme = $owing->groupBy('programme_id')
            ->map(fn ($group) => [
                'label'  => $group->first()->programme->code,
                'amount' => $group->sum(fn ($student) => $student->balance()),
                'count'  => $group->count(),
            ])
            ->sortByDesc('amount')
            ->values();

        return [
            'billed'         => $billed,
            'collected'      => $collected,
            'outstanding'    => $owing->sum(fn ($student) => $student->balance()),
            'owing'          => $owing->count(),
            'collectionRate' => $billed > 0 ? round($collected / $billed * 100, 1) : null,
            'thisMonth'      => (float) Payment::whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount'),
            'months'         => $months,
            'byProgramme'    => $outstandingByProgramme,
        ];
    }

    private function timetableFigures(?Term $term): array
    {
        $slots = TimetableSlot::with('classroom')
            ->whereHas('classSession', fn ($query) => $query->where('term_id', $term?->id))
            ->get();

        $rooms = Classroom::count();
        $weeklyHoursPerRoom = (Timetable::LAST_HOUR - Timetable::FIRST_HOUR) * 5;   // Monday to Friday
        $hours = $slots->sum(fn ($slot) => Timetable::hours($slot));

        $busiest = $slots->groupBy('classroom_id')
            ->map(fn ($group) => [
                'label' => $group->first()->classroom->code,
                'name'  => $group->first()->classroom->name,
                'hours' => $group->sum(fn ($slot) => Timetable::hours($slot)),
            ])
            ->sortByDesc('hours')
            ->take(5)
            ->values();

        return [
            'sessions'    => ClassSession::where('term_id', $term?->id)->count(),
            'classes'     => $slots->count(),
            'hours'       => $hours,
            'utilisation' => $rooms ? round($hours / ($rooms * $weeklyHoursPerRoom) * 100, 1) : null,
            'rooms'       => $rooms,
            'busiest'     => $busiest,
            'perRoomMax'  => $weeklyHoursPerRoom,
        ];
    }
}

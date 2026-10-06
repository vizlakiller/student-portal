<?php

namespace App\Http\Controllers;

use App\Models\Charge;
use App\Models\Payment;
use App\Models\Programme;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Accountant: overview of fees owed and paid, and each student's statement.
 */
class FinanceController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only('search', 'programme', 'owing');

        // Every student's balance. Fine for a few thousand students.
        $students = Student::with('programme')
            ->withBalance()
            ->filter($filters)
            ->get()
            ->when($filters['owing'] ?? false, fn ($all) => $all->filter(fn (Student $student) => $student->balance() > 0))
            ->sortByDesc(fn (Student $student) => $student->balance())
            ->values();

        $perPage = 20;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $paginated = new LengthAwarePaginator(
            $students->forPage($page, $perPage), $students->count(), $perPage, $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        $allBalances = Student::withBalance()->get()->map->balance();

        $totals = [
            'billed'      => (float) Charge::sum('amount'),
            'collected'   => (float) Payment::sum('amount'),
            'outstanding' => $allBalances->filter(fn ($balance) => $balance > 0)->sum(),
            'owing'       => $allBalances->filter(fn ($balance) => $balance > 0)->count(),
            'this_month'  => (float) Payment::whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount'),
        ];

        $recentPayments = Payment::with('student')->latest('paid_at')->latest('id')->take(6)->get();
        $programmes = Programme::orderBy('code')->get();

        return view('finance.index', [
            'students'       => $paginated,
            'filters'        => $filters,
            'totals'         => $totals,
            'recentPayments' => $recentPayments,
            'programmes'     => $programmes,
        ]);
    }

    /**
     * One student's statement: charges and payments in date order with a running balance.
     */
    public function show(Student $student)
    {
        $student->load('programme');

        $charges = $student->charges()->with('creator')->get()->map(fn (Charge $charge) => [
            'date'        => $charge->created_at,
            'description' => $charge->description.($charge->due_date ? ' (due '.$charge->due_date->format('j M Y').')' : ''),
            'charge'      => (float) $charge->amount,
            'payment'     => null,
            'model'       => $charge,
        ]);

        $payments = $student->payments()->with('receiver')->get()->map(fn (Payment $payment) => [
            'date'        => $payment->paid_at,
            'description' => 'Payment, '.$payment->method.' ('.$payment->receipt_no.')',
            'charge'      => null,
            'payment'     => (float) $payment->amount,
            'model'       => $payment,
        ]);

        $running = 0;
        $entries = $charges->concat($payments)
            ->sortBy(fn ($entry) => $entry['date']->format('Y-m-d').($entry['charge'] !== null ? '0' : '1'))
            ->values()
            ->map(function ($entry) use (&$running) {
                $running += ($entry['charge'] ?? 0) - ($entry['payment'] ?? 0);
                $entry['balance'] = round($running, 2);

                return $entry;
            });

        return view('finance.show', [
            'student' => $student,
            'entries' => $entries,
            'charged' => $charges->sum('charge'),
            'paid'    => $payments->sum('payment'),
            'balance' => round($charges->sum('charge') - $payments->sum('payment'), 2),
        ]);
    }
}

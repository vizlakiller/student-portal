<?php

namespace App\Http\Controllers;

use App\Models\ClassSession;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Lecturers: see their sessions and enter marks for the students in them.
 */
class MarksController extends Controller
{
    public function index(Request $request)
    {
        $term = $request->filled('term') ? Term::findOrFail($request->integer('term')) : Term::current();

        $sessions = $request->user()->classSessions()
            ->with(['subject', 'lecturers', 'slots.classroom'])
            ->withCount([
                'results',
                'results as marked_count' => fn ($query) => $query->whereNotNull('marks'),
            ])
            ->where('term_id', $term?->id)
            ->get()
            ->sortBy(fn ($session) => $session->subject->code.$session->name);

        return view('marks.index', [
            'sessions' => $sessions,
            'term'     => $term,
            'terms'    => Term::orderByDesc('starts_on')->orderByDesc('id')->get(),
        ]);
    }

    public function edit(ClassSession $classSession)
    {
        Gate::authorize('enter-marks', $classSession);

        $classSession->load(['subject', 'term', 'lecturers']);

        $results = $classSession->results()
            ->with(['student', 'pendingChange'])
            ->get()
            ->sortBy('student.name');

        return view('marks.edit', ['session' => $classSession, 'results' => $results]);
    }

    /**
     * Save all marks on the page at once. Empty boxes mean "no marks yet".
     */
    public function update(Request $request, ClassSession $classSession)
    {
        Gate::authorize('enter-marks', $classSession);

        $data = $request->validate([
            'marks'   => ['array'],
            'marks.*' => ['nullable', 'integer', 'between:0,100'],
        ], [
            'marks.*.integer' => 'Marks must be whole numbers.',
            'marks.*.between' => 'Marks must be between 0 and 100.',
        ]);

        // Only results in this session can be changed here.
        $results = $classSession->results()->whereIn('id', array_keys($data['marks'] ?? []))->get();

        DB::transaction(function () use ($results, $data) {
            foreach ($results as $result) {
                $marks = $data['marks'][$result->id];
                $result->update(['marks' => $marks === null || $marks === '' ? null : (int) $marks]);
            }
        });

        return redirect()
            ->route('marks.edit', $classSession)
            ->with('success', "Marks saved for {$classSession->subject->code} {$classSession->name}.");
    }
}

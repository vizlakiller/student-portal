<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Teachers: see their subjects and enter marks for the registered students.
 */
class MarksController extends Controller
{
    public function index(Request $request)
    {
        $subjects = $request->user()->subjects()
            ->withCount([
                'results',
                'results as marked_count' => fn ($query) => $query->whereNotNull('marks'),
            ])
            ->orderBy('code')
            ->get();

        return view('marks.index', compact('subjects'));
    }

    public function edit(Subject $subject)
    {
        Gate::authorize('enter-marks', $subject);

        $results = $subject->results()
            ->with(['student', 'pendingChange'])
            ->get()
            ->sortBy('student.name');

        return view('marks.edit', compact('subject', 'results'));
    }

    /**
     * Save all marks on the page at once. Empty boxes mean "no marks yet".
     */
    public function update(Request $request, Subject $subject)
    {
        Gate::authorize('enter-marks', $subject);

        $data = $request->validate([
            'marks'   => ['array'],
            'marks.*' => ['nullable', 'integer', 'between:0,100'],
        ], [
            'marks.*.integer' => 'Marks must be whole numbers.',
            'marks.*.between' => 'Marks must be between 0 and 100.',
        ]);

        $results = $subject->results()->whereIn('id', array_keys($data['marks'] ?? []))->get();

        DB::transaction(function () use ($results, $data) {
            foreach ($results as $result) {
                $marks = $data['marks'][$result->id];
                $result->update(['marks' => $marks === null || $marks === '' ? null : (int) $marks]);
            }
        });

        return redirect()
            ->route('marks.edit', $subject)
            ->with('success', "Marks saved for {$subject->code}.");
    }
}

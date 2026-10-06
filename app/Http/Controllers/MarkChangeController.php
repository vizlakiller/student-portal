<?php

namespace App\Http\Controllers;

use App\Models\Result;
use App\Models\ResultChangeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Head of department asks to change a mark; the subject's teacher approves
 * or rejects it. The mark only changes when the teacher approves.
 */
class MarkChangeController extends Controller
{
    public function index(Request $request)
    {
        $requests = ResultChangeRequest::visibleTo($request->user())
            ->with(['result.student', 'result.subject.teacher', 'requester', 'decider'])
            ->orderByRaw("status = 'pending' desc")
            ->latest()
            ->paginate(20);

        return view('mark-changes.index', compact('requests'));
    }

    public function create(Result $result)
    {
        Gate::authorize('request-mark-change', $result);

        $result->load(['student', 'subject.teacher']);

        return view('mark-changes.create', compact('result'));
    }

    public function store(Request $request, Result $result)
    {
        Gate::authorize('request-mark-change', $result);

        $data = $request->validate([
            'new_marks' => ['required', 'integer', 'between:0,100', 'not_in:'.$result->marks],
            'reason'    => ['required', 'string', 'max:500'],
        ], [
            'new_marks.not_in' => 'The new marks are the same as the current marks.',
        ], [
            'new_marks' => 'new marks',
        ]);

        $result->changeRequests()->create([
            'old_marks'    => $result->marks,
            'new_marks'    => $data['new_marks'],
            'reason'       => $data['reason'],
            'requested_by' => $request->user()->id,
        ]);

        $teacher = $result->subject->teacher?->name;

        return redirect()
            ->route('students.show', $result->student_id)
            ->with('success', $teacher
                ? "Change sent to {$teacher} for approval. The mark changes once they approve it."
                : 'Change saved, but this subject has no teacher yet. Assign a teacher so it can be approved.');
    }

    public function approve(Request $request, ResultChangeRequest $changeRequest)
    {
        Gate::authorize('decide-mark-change', $changeRequest);

        DB::transaction(function () use ($request, $changeRequest) {
            $changeRequest->result->update(['marks' => $changeRequest->new_marks]);
            $changeRequest->update([
                'status'        => 'approved',
                'decided_by'    => $request->user()->id,
                'decided_at'    => now(),
                'decision_note' => $request->input('decision_note'),
            ]);
        });

        return back()->with('success', 'Change approved. The new mark is now on the student\'s record.');
    }

    public function reject(Request $request, ResultChangeRequest $changeRequest)
    {
        Gate::authorize('decide-mark-change', $changeRequest);

        $request->validate(['decision_note' => ['nullable', 'string', 'max:500']]);

        $changeRequest->update([
            'status'        => 'rejected',
            'decided_by'    => $request->user()->id,
            'decided_at'    => now(),
            'decision_note' => $request->input('decision_note'),
        ]);

        return back()->with('success', 'Change rejected. The mark stays as it was.');
    }
}

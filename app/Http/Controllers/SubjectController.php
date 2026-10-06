<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubjectController extends Controller
{
    public function index()
    {
        $subjects = Subject::withCount('results')->orderBy('code')->paginate(20);

        return view('subjects.index', compact('subjects'));
    }

    public function create()
    {
        return view('subjects.create', ['subject' => new Subject(['credit_hours' => 3])]);
    }

    public function store(Request $request)
    {
        $subject = Subject::create($this->validateSubject($request));

        return redirect()
            ->route('subjects.index')
            ->with('success', "Subject {$subject->code} added.");
    }

    public function edit(Subject $subject)
    {
        return view('subjects.edit', compact('subject'));
    }

    public function update(Request $request, Subject $subject)
    {
        $subject->update($this->validateSubject($request, $subject));

        return redirect()
            ->route('subjects.index')
            ->with('success', "Subject {$subject->code} updated.");
    }

    public function destroy(Subject $subject)
    {
        if ($subject->results()->exists()) {
            return back()->with('error', "{$subject->code} has student results recorded, so it can't be deleted.");
        }

        $subject->delete();

        return redirect()
            ->route('subjects.index')
            ->with('success', "Subject {$subject->code} deleted.");
    }

    private function validateSubject(Request $request, ?Subject $subject = null): array
    {
        return $request->validate([
            'code'         => ['required', 'string', 'max:20', Rule::unique('subjects')->ignore($subject)],
            'name'         => ['required', 'string', 'max:255'],
            'credit_hours' => ['required', 'integer', 'between:1,6'],
        ]);
    }
}

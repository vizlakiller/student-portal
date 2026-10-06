<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubjectController extends Controller
{
    public function index()
    {
        $subjects = Subject::with('teacher')->withCount('results')->orderBy('code')->paginate(20);

        return view('subjects.index', compact('subjects'));
    }

    public function create()
    {
        return view('subjects.create', [
            'subject'  => new Subject(['credit_hours' => 3]),
            'teachers' => $this->teacherOptions(),
        ]);
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
        return view('subjects.edit', [
            'subject'  => $subject,
            'teachers' => $this->teacherOptions(),
        ]);
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
            return back()->with('error', "{$subject->code} has students registered, so it can't be deleted.");
        }

        $subject->delete();

        return redirect()
            ->route('subjects.index')
            ->with('success', "Subject {$subject->code} deleted.");
    }

    /** Teachers for the "Teacher" dropdown: [id => name]. */
    private function teacherOptions(): array
    {
        return User::where('role', 'teacher')->orderBy('name')->pluck('name', 'id')->all();
    }

    private function validateSubject(Request $request, ?Subject $subject = null): array
    {
        $rules = [
            'code'         => ['required', 'string', 'max:20', Rule::unique('subjects')->ignore($subject)],
            'name'         => ['required', 'string', 'max:255'],
            'credit_hours' => ['required', 'integer', 'between:1,6'],
        ];

        // Only users allowed to assign teachers can set (or change) the teacher.
        if ($request->user()->can('assign-teachers')) {
            $rules['teacher_id'] = ['nullable', Rule::exists('users', 'id')->where('role', 'teacher')];
        }

        return $request->validate($rules, [], ['teacher_id' => 'teacher']);
    }
}

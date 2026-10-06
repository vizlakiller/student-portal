<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Subjects. A head of department manages the subjects of the departments
 * they head and chooses their lecturers (a subject can have several).
 */
class SubjectController extends Controller
{
    public function index(Request $request)
    {
        $departmentId = $request->integer('department') ?: null;

        $subjects = Subject::with(['department', 'lecturers'])
            ->withCount('results')
            ->when($departmentId, fn ($query) => $query->where('department_id', $departmentId))
            ->orderBy('code')
            ->paginate(20)
            ->withQueryString();

        return view('subjects.index', [
            'subjects'     => $subjects,
            'departments'  => Department::orderBy('code')->get(),
            'departmentId' => $departmentId,
        ]);
    }

    public function create(Request $request)
    {
        return view('subjects.create', [
            'subject'     => new Subject(['credit_hours' => 3]),
            'departments' => $this->departmentOptions($request),
            'lecturers'   => $this->lecturerOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateSubject($request);

        $subject = DB::transaction(function () use ($data) {
            $subject = Subject::create($data);
            $subject->lecturers()->sync($data['lecturer_ids'] ?? []);

            return $subject;
        });

        return redirect()->route('subjects.index')->with('success', "Subject {$subject->code} added.");
    }

    public function edit(Request $request, Subject $subject)
    {
        Gate::authorize('manage-subject', $subject);

        return view('subjects.edit', [
            'subject'     => $subject->load('lecturers'),
            'departments' => $this->departmentOptions($request),
            'lecturers'   => $this->lecturerOptions(),
        ]);
    }

    public function update(Request $request, Subject $subject)
    {
        Gate::authorize('manage-subject', $subject);

        $data = $this->validateSubject($request, $subject);

        DB::transaction(function () use ($subject, $data) {
            $subject->update($data);

            if (array_key_exists('lecturer_ids', $data)) {
                $changes = $subject->lecturers()->sync($data['lecturer_ids']);

                // Lecturers taken off the subject also leave its sessions in the current term.
                if ($changes['detached'] && $term = \App\Models\Term::current()) {
                    foreach ($subject->sessions()->where('term_id', $term->id)->get() as $session) {
                        $session->lecturers()->detach($changes['detached']);
                    }
                }
            }
        });

        return redirect()->route('subjects.index')->with('success', "Subject {$subject->code} updated.");
    }

    public function destroy(Subject $subject)
    {
        Gate::authorize('manage-subject', $subject);

        if ($subject->results()->exists() || $subject->sessions()->exists()) {
            return back()->with('error', "{$subject->code} has sessions or registered students, so it can't be deleted.");
        }

        $subject->delete();

        return redirect()->route('subjects.index')->with('success', "Subject {$subject->code} deleted.");
    }

    /** Departments this user may put subjects in: the super admin all, a HOD their own. */
    private function departmentOptions(Request $request): array
    {
        $user = $request->user();

        return Department::orderBy('code')
            ->when(! $user->isSuperAdmin(), fn ($query) => $query->whereIn('id', $user->headedDepartmentIds()))
            ->get()
            ->mapWithKeys(fn ($department) => [$department->id => $department->code.' – '.$department->name])
            ->all();
    }

    /** All lecturers: [id => name]. */
    private function lecturerOptions(): array
    {
        return User::where('role', 'lecturer')->orderBy('name')->pluck('name', 'id')->all();
    }

    private function validateSubject(Request $request, ?Subject $subject = null): array
    {
        $user = $request->user();

        $rules = [
            'code'          => ['required', 'string', 'max:20', Rule::unique('subjects')->ignore($subject)],
            'name'          => ['required', 'string', 'max:255'],
            'credit_hours'  => ['required', 'integer', 'between:1,6'],
            // A head of department may only use departments they head.
            'department_id' => [$user->isSuperAdmin() ? 'nullable' : 'required',
                Rule::in(array_keys($this->departmentOptions($request)))],
        ];

        if ($user->can('assign-lecturers')) {
            $rules['lecturer_ids'] = ['array'];
            $rules['lecturer_ids.*'] = ['integer', Rule::exists('users', 'id')->where('role', 'lecturer')];
        }

        $data = $request->validate($rules, [
            'department_id.in' => 'Choose one of the departments you head.',
        ], ['department_id' => 'department', 'lecturer_ids.*' => 'lecturer']);

        if ($user->can('assign-lecturers')) {
            $data['lecturer_ids'] = $data['lecturer_ids'] ?? [];
        }

        return $data;
    }
}

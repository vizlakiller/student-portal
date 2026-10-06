<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Programme;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProgrammeController extends Controller
{
    public function index()
    {
        $programmes = Programme::with('department')->withCount('students')->orderBy('code')->get();

        return view('programmes.index', compact('programmes'));
    }

    public function create()
    {
        return view('programmes.create', ['programme' => new Programme, 'departments' => $this->departmentOptions()]);
    }

    public function store(Request $request)
    {
        $programme = Programme::create($this->validateProgramme($request));

        return redirect()
            ->route('programmes.index')
            ->with('success', "Programme {$programme->code} added.");
    }

    public function edit(Programme $programme)
    {
        return view('programmes.edit', ['programme' => $programme, 'departments' => $this->departmentOptions()]);
    }

    public function update(Request $request, Programme $programme)
    {
        $programme->update($this->validateProgramme($request, $programme));

        return redirect()
            ->route('programmes.index')
            ->with('success', "Programme {$programme->code} updated.");
    }

    public function destroy(Programme $programme)
    {
        if ($programme->students()->exists()) {
            return back()->with('error', "{$programme->code} still has students. Move or delete them first.");
        }

        $programme->delete();

        return redirect()
            ->route('programmes.index')
            ->with('success', "Programme {$programme->code} deleted.");
    }

    private function departmentOptions(): array
    {
        return Department::orderBy('code')->get()
            ->mapWithKeys(fn ($department) => [$department->id => $department->code.' – '.$department->name])
            ->all();
    }

    private function validateProgramme(Request $request, ?Programme $programme = null): array
    {
        return $request->validate([
            'code'          => ['required', 'string', 'max:20', Rule::unique('programmes')->ignore($programme)],
            'name'          => ['required', 'string', 'max:255'],
            'level'         => ['required', Rule::in(Programme::LEVELS)],
            'department_id' => ['nullable', 'exists:departments,id'],
        ], [], ['department_id' => 'department']);
    }
}

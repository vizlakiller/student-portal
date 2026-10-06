<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Departments and their heads. The super admin manages them; others can view.
 * Each department has one head; one head may lead several departments.
 */
class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::with('hod')
            ->withCount(['programmes', 'subjects'])
            ->orderBy('code')
            ->get();

        return view('departments.index', compact('departments'));
    }

    public function create()
    {
        return view('departments.create', ['department' => new Department, 'hods' => $this->hodOptions()]);
    }

    public function store(Request $request)
    {
        $department = Department::create($this->validateDepartment($request));

        return redirect()->route('departments.index')->with('success', "Department {$department->code} added.");
    }

    public function edit(Department $department)
    {
        return view('departments.edit', ['department' => $department, 'hods' => $this->hodOptions()]);
    }

    public function update(Request $request, Department $department)
    {
        $department->update($this->validateDepartment($request, $department));

        return redirect()->route('departments.index')->with('success', "Department {$department->code} updated.");
    }

    public function destroy(Department $department)
    {
        if ($department->programmes()->exists() || $department->subjects()->exists()) {
            return back()->with('error', "{$department->code} still has programmes or subjects. Move them to another department first.");
        }

        $department->delete();

        return redirect()->route('departments.index')->with('success', "Department {$department->code} deleted.");
    }

    /** Heads of department for the dropdown: [id => name]. */
    private function hodOptions(): array
    {
        return User::where('role', 'hod')->orderBy('name')->pluck('name', 'id')->all();
    }

    private function validateDepartment(Request $request, ?Department $department = null): array
    {
        return $request->validate([
            'code'   => ['required', 'string', 'max:20', Rule::unique('departments')->ignore($department)],
            'name'   => ['required', 'string', 'max:255'],
            'hod_id' => ['nullable', Rule::exists('users', 'id')->where('role', 'hod')],
        ], [], ['hod_id' => 'head of department']);
    }
}

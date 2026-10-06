<?php

namespace App\Http\Controllers;

use App\Models\User;

/**
 * Teacher details for admin staff, heads of department and the registrar.
 * Teacher accounts are added by the super admin under "User accounts".
 */
class TeacherController extends Controller
{
    public function index()
    {
        $teachers = User::where('role', 'teacher')
            ->with(['subjects' => fn ($query) => $query->orderBy('code')])
            ->orderBy('name')
            ->get();

        return view('teachers.index', compact('teachers'));
    }

    public function show(User $teacher)
    {
        abort_unless($teacher->hasRole('teacher'), 404);

        $teacher->load(['subjects' => fn ($query) => $query->withCount('results')->orderBy('code')]);

        return view('teachers.show', compact('teacher'));
    }
}

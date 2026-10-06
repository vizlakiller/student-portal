<?php

namespace App\Http\Controllers;

use App\Models\Programme;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProgrammeController extends Controller
{
    public function index()
    {
        $programmes = Programme::withCount('students')->orderBy('code')->get();

        return view('programmes.index', compact('programmes'));
    }

    public function create()
    {
        return view('programmes.create', ['programme' => new Programme]);
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
        return view('programmes.edit', compact('programme'));
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

    private function validateProgramme(Request $request, ?Programme $programme = null): array
    {
        return $request->validate([
            'code'  => ['required', 'string', 'max:20', Rule::unique('programmes')->ignore($programme)],
            'name'  => ['required', 'string', 'max:255'],
            'level' => ['required', Rule::in(Programme::LEVELS)],
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Super admin: academic terms. Sessions and timetables belong to a term;
 * the current term is used for registration and "My timetable".
 */
class TermController extends Controller
{
    public function index()
    {
        $terms = Term::withCount('sessions')->orderByDesc('starts_on')->orderByDesc('id')->get();

        return view('terms.index', compact('terms'));
    }

    public function create()
    {
        return view('terms.create', ['term' => new Term]);
    }

    public function store(Request $request)
    {
        $term = Term::create($this->validateTerm($request));

        if ($request->boolean('is_current') || ! Term::current()) {
            $term->makeCurrent();
        }

        return redirect()->route('terms.index')->with('success', "Term {$term->name} added.");
    }

    public function edit(Term $term)
    {
        return view('terms.edit', compact('term'));
    }

    public function update(Request $request, Term $term)
    {
        $term->update($this->validateTerm($request, $term));

        return redirect()->route('terms.index')->with('success', "Term {$term->name} updated.");
    }

    public function destroy(Term $term)
    {
        if ($term->sessions()->exists()) {
            return back()->with('error', "{$term->name} has sessions, so it can't be deleted.");
        }

        if ($term->is_current) {
            return back()->with('error', 'Make another term current before deleting this one.');
        }

        $term->delete();

        return redirect()->route('terms.index')->with('success', "Term {$term->name} deleted.");
    }

    public function makeCurrent(Term $term)
    {
        $term->makeCurrent();

        return redirect()->route('terms.index')->with('success', "{$term->name} is now the current term.");
    }

    private function validateTerm(Request $request, ?Term $term = null): array
    {
        return $request->validate([
            'name'      => ['required', 'string', 'max:60', Rule::unique('terms')->ignore($term)],
            'starts_on' => ['nullable', 'date'],
            'ends_on'   => ['nullable', 'date', 'after:starts_on'],
        ], [], ['starts_on' => 'start date', 'ends_on' => 'end date']);
    }
}

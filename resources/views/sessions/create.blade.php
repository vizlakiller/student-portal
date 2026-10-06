@extends('layouts.app')
@section('title', 'Add session')
@section('subtitle', 'A session is one group of a subject in a term, with its own lecturers, students and class times.')
@section('back')<a href="{{ route('sessions.index') }}">Sessions</a>@endsection

@section('content')
    @if ($subjects->isEmpty())
        <div class="panel empty-state">
            <p>You can add sessions for subjects in the departments you head, but none have subjects yet.</p>
        </div>
    @else
        {{-- Step 1: choose the subject (reloads the page with its lecturers) --}}
        <form method="GET" action="{{ route('sessions.create') }}" class="panel form narrow">
            <h2>1. Subject</h2>
            <div class="inline-field">
                <x-select label="Subject" name="subject" placeholder="Choose a subject" required
                          :options="$subjects->mapWithKeys(fn ($s) => [$s->id => $s->code.' – '.$s->name])" :value="$subject?->id"
                          onchange="this.form.submit()" />
                <noscript><button type="submit" class="btn">Next</button></noscript>
            </div>
        </form>

        @if ($subject)
            <form method="POST" action="{{ route('sessions.store') }}" class="panel form narrow">
                @csrf
                <input type="hidden" name="subject_id" value="{{ $subject->id }}">
                <h2>2. Session details for {{ $subject->code }}</h2>

                <div class="form-grid">
                    <x-select label="Term" name="term_id" required
                              :options="$terms->mapWithKeys(fn ($t) => [$t->id => $t->name.($t->is_current ? ' (current)' : '')])"
                              :value="$current?->id" />
                    <x-input label="Session name" name="name" required placeholder="Group A" maxlength="60" />
                </div>
                <x-input label="Maximum students" name="capacity" type="number" min="1" max="1000"
                         hint="Leave empty for no limit." />

                @include('sessions._lecturers', ['subject' => $subject, 'selected' => old('lecturer_ids', [])])

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Add session</button>
                    <a href="{{ route('sessions.index') }}" class="btn btn-quiet">Cancel</a>
                </div>
            </form>
        @endif
    @endif
@endsection

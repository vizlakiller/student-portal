@extends('layouts.app')
@section('title', 'Register subjects')
@section('subtitle', $student->name.' ('.$student->student_no.')')
@section('back')<a href="{{ route('students.show', $student) }}">Back to {{ $student->name }}</a>@endsection

@section('content')
    <form method="POST" action="{{ route('registrations.store', $student) }}" class="panel form narrow">
        @csrf

        <fieldset class="form-section">
            <legend>Subjects</legend>
            @error('subject_ids')<p class="field-error">{{ $message }}</p>@enderror
            @error('subject_ids.*')<p class="field-error">{{ $message }}</p>@enderror
            <div class="checkbox-list">
                @foreach ($subjects as $subject)
                    <label class="checkbox-row">
                        <input type="checkbox" name="subject_ids[]" value="{{ $subject->id }}"
                               @checked(in_array($subject->id, old('subject_ids', [])))>
                        <span>
                            <strong>{{ $subject->code }}</strong> {{ $subject->name }}
                            <span class="muted small">{{ $subject->credit_hours }} credits, {{ $subject->teacher?->name ?? 'no teacher yet' }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
        </fieldset>

        <x-input label="Semester" name="semester" type="number" min="1" max="12" :value="$student->semester" required
                 hint="The semester the student takes these subjects in." />

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Register</button>
            <a href="{{ route('students.show', $student) }}" class="btn btn-quiet">Cancel</a>
        </div>
    </form>
@endsection

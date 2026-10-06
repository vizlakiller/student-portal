@extends('layouts.app')
@section('title', 'Add result')
@section('subtitle', $student->name.' ('.$student->student_no.')')
@section('back')<a href="{{ route('students.show', $student) }}">Back to {{ $student->name }}</a>@endsection

@section('content')
    <form method="POST" action="{{ route('students.results.store', $student) }}" class="panel form narrow">
        @csrf

        <x-select label="Subject" name="subject_id" placeholder="Choose subject" required
                  :options="$subjects->mapWithKeys(fn ($s) => [$s->id => $s->code.' – '.$s->name.' ('.$s->credit_hours.' credits)'])" />

        <div class="form-grid">
            <x-input label="Semester taken" name="semester" type="number" min="1" max="12"
                     :value="$result->semester" required />
            <x-input label="Marks" name="marks" type="number" min="0" max="100" required
                     hint="0 to 100. The grade is worked out automatically." />
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save result</button>
            <a href="{{ route('students.show', $student) }}" class="btn btn-quiet">Cancel</a>
        </div>
    </form>

    @include('results._scale')
@endsection

@extends('layouts.app')
@section('title', 'Edit result')
@section('subtitle', $result->subject->code.' '.$result->subject->name.' for '.$student->name)
@section('back')<a href="{{ route('students.show', $student) }}">Back to {{ $student->name }}</a>@endsection

@section('content')
    <form method="POST" action="{{ route('students.results.update', [$student, $result]) }}" class="panel form narrow">
        @csrf
        @method('PUT')

        <p class="muted">Current grade: @include('partials.grade', ['grade' => $result->grade]) ({{ $result->marks }} marks)</p>

        <div class="form-grid">
            <x-input label="Semester taken" name="semester" type="number" min="1" max="12"
                     :value="$result->semester" required />
            <x-input label="Marks" name="marks" type="number" min="0" max="100"
                     :value="$result->marks" required hint="0 to 100." />
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save changes</button>
            <a href="{{ route('students.show', $student) }}" class="btn btn-quiet">Cancel</a>
        </div>
    </form>

    @include('results._scale')
@endsection

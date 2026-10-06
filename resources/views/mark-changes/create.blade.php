@extends('layouts.app')
@section('title', 'Request a mark change')
@section('subtitle', $result->subject->code.' '.$result->subject->name.' for '.$result->student->name)
@section('back')<a href="{{ route('students.show', $result->student) }}">Back to {{ $result->student->name }}</a>@endsection

@section('content')
    <form method="POST" action="{{ route('mark-changes.store', $result) }}" class="panel form narrow">
        @csrf

        <p>
            Current mark: <strong>{{ $result->marks }}</strong>
            @include('partials.grade', ['grade' => $result->grade])
        </p>
        <p class="muted">
            @if ($result->subject->teacher)
                The mark changes only after {{ $result->subject->teacher->name }}, the subject teacher, approves your request.
            @else
                This subject has no teacher yet, so nobody can approve the change. Assign a teacher on the Subjects page first.
            @endif
        </p>

        <x-input label="New marks" name="new_marks" type="number" min="0" max="100" required />
        <x-textarea label="Reason" name="reason" required rows="3"
                    placeholder="e.g. Re-marking after appeal; exam paper 2 was not counted." />

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Send for approval</button>
            <a href="{{ route('students.show', $result->student) }}" class="btn btn-quiet">Cancel</a>
        </div>
    </form>
@endsection

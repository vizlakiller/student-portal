@extends('layouts.app')
@section('title', 'Register subjects')
@section('subtitle', $student->name.' ('.$student->student_no.'), '.$term->name)
@section('back')<a href="{{ route('students.show', $student) }}">Back to {{ $student->name }}</a>@endsection

@section('content')
    <form method="POST" action="{{ route('registrations.store', $student) }}" class="panel form">
        @csrf

        <p class="muted">Choose a session for each subject the student takes this term. Sessions that clash with each other are refused.</p>
        @error('sessions')<div class="alert alert-error">{{ $message }}</div>@enderror

        @foreach ($sessions as $subjectId => $subjectSessions)
            @php $subject = $subjectSessions->first()->subject; @endphp
            <fieldset class="reg-subject">
                <legend><strong>{{ $subject->code }}</strong> {{ $subject->name }} <span class="muted small">{{ $subject->credit_hours }} credits</span></legend>

                <label class="reg-option">
                    <input type="radio" name="sessions[{{ $subjectId }}]" value="" @checked(! old("sessions.$subjectId"))>
                    <span class="muted">Don't register</span>
                </label>

                @foreach ($subjectSessions as $session)
                    @php $full = $session->isFull($session->results_count); @endphp
                    <label @class(['reg-option', 'is-full' => $full])>
                        <input type="radio" name="sessions[{{ $subjectId }}]" value="{{ $session->id }}"
                               @checked(old("sessions.$subjectId") == $session->id) @disabled($full)>
                        <span>
                            <strong>{{ $session->name }}</strong>
                            <span class="muted small">{{ $session->lecturers->pluck('name')->join(', ') ?: 'No lecturer yet' }}</span>
                            <span class="reg-times">
                                @forelse ($session->slots as $slot)
                                    <span>{{ substr($slot->dayName(), 0, 3) }} {{ $slot->timeRange() }}, {{ $slot->classroom->code }}</span>
                                @empty
                                    <span>No class times yet</span>
                                @endforelse
                            </span>
                        </span>
                        <span class="reg-places">
                            @if ($full)
                                <span class="pending">Full</span>
                            @else
                                {{ $session->results_count }}{{ $session->capacity ? ' / '.$session->capacity : '' }} students
                            @endif
                        </span>
                    </label>
                @endforeach
            </fieldset>
        @endforeach

        <x-input label="Student's semester" name="semester" type="number" min="1" max="12" :value="$student->semester" required
                 hint="The semester of study these subjects count towards." class="narrow-field" />

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Register</button>
            <a href="{{ route('students.show', $student) }}" class="btn btn-quiet">Cancel</a>
        </div>
    </form>
@endsection

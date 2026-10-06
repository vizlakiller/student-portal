@extends('layouts.app')
@section('title', $session->label())
@section('subtitle', $session->subject->name.', '.$session->term->name.($session->term->is_current ? ' (current term)' : ''))
@section('back')<a href="{{ route('sessions.index', ['term' => $session->term_id]) }}">Sessions</a>@endsection

@php $canManage = auth()->user()->can('manage-subject-timetable', $session->subject); @endphp

@section('actions')
    @can('enter-marks', $session)
        <a href="{{ route('marks.edit', $session) }}" class="btn btn-primary">Enter marks</a>
    @endcan
    @if ($canManage)
        <a href="{{ route('sessions.edit', $session) }}" class="btn">Edit session</a>
    @endif
@endsection

@section('content')
    <section class="panel">
        <dl class="details">
            <div><dt>Lecturers</dt><dd>{{ $session->lecturers->pluck('name')->join(', ') ?: 'None yet' }}</dd></div>
            <div><dt>Department</dt><dd>{{ $session->subject->department?->name ?? 'No department' }}</dd></div>
            <div><dt>Students</dt><dd>{{ $students->count() }}{{ $session->capacity ? ' of '.$session->capacity.' places' : ' (no limit)' }}</dd></div>
            <div><dt>Credit hours</dt><dd>{{ $session->subject->credit_hours }}</dd></div>
        </dl>
    </section>

    <section class="panel flush">
        <div class="panel-header inset"><h2>Class times</h2></div>
        @if ($session->slots->isEmpty())
            <p class="empty inset">No class times yet.</p>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Day</th><th>Time</th><th>Classroom</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
                    <tbody>
                        @foreach ($session->slots as $slot)
                            <tr>
                                <td class="strong">{{ $slot->dayName() }}</td>
                                <td class="num-left">{{ $slot->timeRange() }}</td>
                                <td><a href="{{ route('classrooms.show', $slot->classroom) }}">{{ $slot->classroom->label() }}</a> <span class="muted small">({{ $slot->classroom->capacity }} seats)</span></td>
                                <td class="row-actions">
                                    @if ($canManage)
                                        <form method="POST" action="{{ route('slots.destroy', $slot) }}"
                                              onsubmit="return confirm(@js('Remove the class on '.$slot->whenLabel().'?'))">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="link-button danger">Remove</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if ($canManage)
            <form method="POST" action="{{ route('slots.store', $session) }}" class="slot-form">
                @csrf
                <h3>Add a class time</h3>
                @error('clash')
                    <div class="alert alert-error">
                        That time clashes:
                        <ul>@foreach ($errors->get('clash') as $problems) @foreach ((array) $problems as $problem)<li>{{ $problem }}</li>@endforeach @endforeach</ul>
                    </div>
                @enderror
                <div class="slot-fields">
                    <x-select label="Day" name="day" required :options="$days" />
                    <x-input label="Starts" name="starts_at" type="time" required step="900"
                             min="{{ sprintf('%02d:00', \App\Support\Timetable::FIRST_HOUR) }}" max="{{ sprintf('%02d:00', \App\Support\Timetable::LAST_HOUR) }}" />
                    <x-input label="Ends" name="ends_at" type="time" required step="900"
                             min="{{ sprintf('%02d:00', \App\Support\Timetable::FIRST_HOUR) }}" max="{{ sprintf('%02d:00', \App\Support\Timetable::LAST_HOUR) }}" />
                    <x-select label="Classroom" name="classroom_id" required placeholder="Choose room"
                              :options="$classrooms->mapWithKeys(fn ($c) => [$c->id => $c->code.' '.$c->name.' ('.$c->capacity.')'])" />
                    <button type="submit" class="btn btn-primary">Add</button>
                </div>
                <p class="hint">The system refuses times when the room, a lecturer or this session is already busy.</p>
            </form>
        @endif
    </section>

    <section class="panel flush">
        <div class="panel-header inset"><h2>Students</h2></div>
        @if ($students->isEmpty())
            <p class="empty inset">No students yet. The registrar registers students into sessions.</p>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Student no.</th><th>Name</th><th class="num">Semester</th>@can('view-results')<th class="num">Marks</th>@endcan</tr></thead>
                    <tbody>
                        @foreach ($students as $result)
                            <tr>
                                <td class="ref">{{ $result->student->student_no }}</td>
                                <td>
                                    @can('view-students')
                                        <a href="{{ route('students.show', $result->student) }}">{{ $result->student->name }}</a>
                                    @else
                                        {{ $result->student->name }}
                                    @endcan
                                </td>
                                <td class="num">{{ $result->semester }}</td>
                                @can('view-results')
                                    <td class="num">{{ $result->marks ?? '–' }}</td>
                                @endcan
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    @if ($canManage && $students->isEmpty())
        <section class="panel danger-zone">
            <h2>Delete this session</h2>
            <form method="POST" action="{{ route('sessions.destroy', $session) }}"
                  onsubmit="return confirm(@js('Delete '.$session->label().' and its class times?'))">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Delete session</button>
            </form>
        </section>
    @endif
@endsection

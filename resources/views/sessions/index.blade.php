@extends('layouts.app')
@section('title', 'Sessions')
@section('subtitle', $term ? 'Groups of each subject in '.$term->name.', with their lecturers and class times.' : 'No term selected. The super admin sets the current term under Terms.')

@section('actions')
    @can('manage-timetable')
        <a href="{{ route('sessions.create') }}" class="btn btn-primary">Add session</a>
    @endcan
@endsection

@section('content')
    <form method="GET" action="{{ route('sessions.index') }}" class="filter-bar">
        <div class="field">
            <label for="term">Term</label>
            <select id="term" name="term">
                @foreach ($terms as $option)
                    <option value="{{ $option->id }}" @selected($term?->id === $option->id)>{{ $option->name }}{{ $option->is_current ? ' (current)' : '' }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="department">Department</label>
            <select id="department" name="department">
                <option value="">All departments</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected($departmentId === $department->id)>{{ $department->code }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-buttons">
            <button type="submit" class="btn btn-primary">Show</button>
        </div>
    </form>

    @forelse ($sessions as $subjectSessions)
        @php $subject = $subjectSessions->first()->subject; @endphp
        <section class="panel flush subject-sessions">
            <div class="semester-header">
                <h3>{{ $subject->code }} {{ $subject->name }}</h3>
                <span>{{ $subject->department?->code ?? 'No department' }}, {{ $subjectSessions->count() }} {{ str('session')->plural($subjectSessions->count()) }}</span>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>Session</th><th>Lecturers</th><th>Class times</th><th class="num">Students</th><th><span class="visually-hidden">Actions</span></th></tr>
                    </thead>
                    <tbody>
                        @foreach ($subjectSessions as $session)
                            <tr>
                                <td><a href="{{ route('sessions.show', $session) }}" class="strong">{{ $session->name }}</a></td>
                                <td>{!! $session->lecturers->isNotEmpty() ? e($session->lecturers->pluck('name')->join(', ')) : '<span class="pending">No lecturer</span>' !!}</td>
                                <td>
                                    @forelse ($session->slots as $slot)
                                        <div class="small">{{ $slot->whenLabel() }}, {{ $slot->classroom->code }}</div>
                                    @empty
                                        <span class="pending">No class times</span>
                                    @endforelse
                                </td>
                                <td class="num">{{ $session->results_count }}{{ $session->capacity ? ' / '.$session->capacity : '' }}</td>
                                <td class="row-actions">
                                    <a href="{{ route('sessions.show', $session) }}">Open</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @empty
        <div class="panel empty-state">
            <p>No sessions {{ $term ? 'in '.$term->name : '' }} yet.</p>
            @can('manage-timetable')
                <a href="{{ route('sessions.create') }}" class="btn btn-primary">Add the first session</a>
            @endcan
        </div>
    @endforelse
@endsection

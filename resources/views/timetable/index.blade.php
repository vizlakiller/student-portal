@extends('layouts.app')
@section('title', 'Weekly timetable')
@section('subtitle', $term ? $term->name.': '.$slotCount.' '.str('class')->plural($slotCount).' a week'.(array_filter($filters) ? ' match your filters.' : '.') : 'No term selected.')

@section('actions')
    <button type="button" class="btn" onclick="window.print()">Print</button>
    @can('manage-timetable')
        <a href="{{ route('sessions.index') }}" class="btn btn-primary">Manage sessions</a>
    @endcan
@endsection

@section('content')
    <form method="GET" action="{{ route('timetable.index') }}" class="filter-bar">
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
                <option value="">All</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected(($filters['department'] ?? '') == $department->id)>{{ $department->code }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="lecturer">Lecturer</label>
            <select id="lecturer" name="lecturer">
                <option value="">All</option>
                @foreach ($lecturers as $lecturer)
                    <option value="{{ $lecturer->id }}" @selected(($filters['lecturer'] ?? '') == $lecturer->id)>{{ $lecturer->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="classroom">Classroom</label>
            <select id="classroom" name="classroom">
                <option value="">All</option>
                @foreach ($classrooms as $classroom)
                    <option value="{{ $classroom->id }}" @selected(($filters['classroom'] ?? '') == $classroom->id)>{{ $classroom->code }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-buttons">
            <button type="submit" class="btn btn-primary">Show</button>
            @if (array_filter($filters))
                <a href="{{ route('timetable.index', ['term' => $term?->id]) }}" class="btn btn-quiet">Clear</a>
            @endif
        </div>
    </form>

    @if ($slotCount === 0)
        <div class="panel empty-state">
            <p>No classes {{ $term ? 'in '.$term->name : '' }}{{ array_filter($filters) ? ' match these filters' : ' yet' }}.</p>
        </div>
    @else
        @include('timetable._grid', ['days' => $days])
    @endif
@endsection

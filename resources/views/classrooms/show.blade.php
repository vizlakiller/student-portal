@extends('layouts.app')
@section('title', $classroom->code.' '.$classroom->name)
@section('subtitle', $classroom->type.', '.$classroom->capacity.' seats'.($classroom->building ? ', '.$classroom->building : ''))
@section('back')<a href="{{ route('classrooms.index') }}">Classrooms</a>@endsection

@section('actions')
    <button type="button" class="btn" onclick="window.print()">Print</button>
@endsection

@section('content')
    <form method="GET" class="filter-bar">
        <div class="field">
            <label for="term">Term</label>
            <select id="term" name="term" onchange="this.form.submit()">
                @foreach ($terms as $option)
                    <option value="{{ $option->id }}" @selected($term?->id === $option->id)>{{ $option->name }}{{ $option->is_current ? ' (current)' : '' }}</option>
                @endforeach
            </select>
        </div>
        <p class="muted filter-note">{{ rtrim(rtrim(number_format($hours, 1), '0'), '.') }} hours booked per week.</p>
        <noscript><button type="submit" class="btn">Show</button></noscript>
    </form>

    @include('timetable._grid', ['days' => $days, 'show' => ['lecturers']])
@endsection

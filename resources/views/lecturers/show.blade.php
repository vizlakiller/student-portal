@extends('layouts.app')
@section('title', $lecturer->name)
@section('subtitle', 'Lecturer'.($lecturer->staff_no ? ', '.$lecturer->staff_no : ''))
@section('back')<a href="{{ route('lecturers.index') }}">Lecturers</a>@endsection

@section('content')
    <section class="panel">
        <dl class="details">
            <div><dt>Email</dt><dd><a href="mailto:{{ $lecturer->email }}">{{ $lecturer->email }}</a></dd></div>
            <div><dt>Phone</dt><dd>{{ $lecturer->phone ?? 'Not recorded' }}</dd></div>
            <div><dt>Subjects</dt><dd>{{ $lecturer->lecturedSubjects->map(fn ($s) => $s->code.' '.$s->name)->join(', ') ?: 'None yet' }}</dd></div>
            <div><dt>Teaching {{ $term ? 'in '.$term->name : '' }}</dt><dd>{{ $sessions->count() }} {{ str('session')->plural($sessions->count()) }}, {{ rtrim(rtrim(number_format($hours, 1), '0'), '.') }} hours a week</dd></div>
        </dl>
    </section>

    @if ($sessions->isNotEmpty())
        <div class="section-header"><h2>Weekly timetable</h2></div>
        @include('timetable._grid', ['days' => $days, 'show' => ['room']])
    @endif
@endsection

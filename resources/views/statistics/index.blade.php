@extends('layouts.app')
@section('title', 'Statistics')
@section('subtitle', 'The whole college at a glance'.($term ? '. Registration and timetable figures are for '.$term->name.'.' : '.'))

@use('App\Support\Branding')

@section('actions')
    <button type="button" class="btn" onclick="window.print()">Print</button>
@endsection

@php
    // Small helper for "12.5%" or "–" when there is nothing to show yet
    $percent = fn ($value) => $value === null ? '–' : rtrim(rtrim(number_format($value, 1), '0'), '.').'%';
@endphp

@section('content')
    {{-- ---------- Students ---------- --}}
    <h2 class="stats-heading">Students</h2>
    <section class="stat-strip" aria-label="Students">
        <a href="{{ route('students.index') }}" class="stat">
            <span class="stat-value">{{ $students['total'] }}</span>
            <span class="stat-label">Students on record</span>
        </a>
        <a href="{{ route('students.index', ['status' => 'Active']) }}" class="stat">
            <span class="stat-value">{{ $students['active'] }}</span>
            <span class="stat-label">Currently active</span>
        </a>
        <div class="stat">
            <span class="stat-value">{{ $students['new_intake'] }}</span>
            <span class="stat-label">New intake in {{ now()->year }}</span>
        </div>
        <a href="{{ route('students.index', ['status' => 'Graduated']) }}" class="stat">
            <span class="stat-value">{{ $students['graduated'] }}</span>
            <span class="stat-label">Graduated</span>
        </a>
    </section>

    <div class="grid-2">
        <section class="panel">
            <h3 class="panel-title">Students by programme</h3>
            @php $max = max($students['byProgramme']->max('students_count'), 1); @endphp
            @foreach ($students['byProgramme'] as $programme)
                <div class="hbar" title="{{ $programme->name }}: {{ $programme->students_count }} students, {{ $programme->active_count }} active">
                    <span class="hbar-label">{{ $programme->code }}</span>
                    <span class="hbar-track"><span class="hbar-fill" style="width: {{ $programme->students_count / $max * 100 }}%"></span></span>
                    <span class="hbar-value">{{ $programme->students_count }}</span>
                </div>
            @endforeach
        </section>
        <section class="panel">
            <h3 class="panel-title">Students by status</h3>
            <ul class="status-list">
                @foreach ($students['statuses'] as $status => $total)
                    <li><a href="{{ route('students.index', ['status' => $status]) }}">@include('partials.status', ['status' => $status]) <span class="num">{{ $total }}</span></a></li>
                @endforeach
            </ul>
        </section>
    </div>

    {{-- ---------- Registration and marking ---------- --}}
    <h2 class="stats-heading">Registration and marking{{ $term ? ', '.$term->name : '' }}</h2>
    @if (! $term)
        <p class="panel empty">No current term is set, so there are no registrations to show.</p>
    @else
        <section class="stat-strip three" aria-label="Registration">
            <div class="stat">
                <span class="stat-value">{{ $register['count'] }}</span>
                <span class="stat-label">Subject registrations</span>
            </div>
            <div class="stat">
                <span class="stat-value">{{ $register['students'] }}</span>
                <span class="stat-label">Students registered</span>
            </div>
            <div class="stat">
                <span class="stat-value">{{ $percent($register['count'] ? $register['marked'] / $register['count'] * 100 : null) }}</span>
                <span class="stat-label">Marked so far ({{ $register['marked'] }} of {{ $register['count'] }})</span>
            </div>
        </section>

        @if ($register['byDepartment']->isNotEmpty())
            <section class="panel">
                <h3 class="panel-title">Registrations by department</h3>
                @php $max = max($register['byDepartment']->max('count'), 1); @endphp
                @foreach ($register['byDepartment'] as $row)
                    <div class="hbar wide-label" title="{{ $row['name'] }}: {{ $row['count'] }} registrations, {{ $row['marked'] }} marked">
                        <span class="hbar-label">{{ $row['label'] }}</span>
                        <span class="hbar-track"><span class="hbar-fill" style="width: {{ $row['count'] / $max * 100 }}%"></span></span>
                        <span class="hbar-value">{{ $row['count'] }}</span>
                    </div>
                @endforeach
            </section>
        @endif
    @endif

    {{-- ---------- Results ---------- --}}
    <h2 class="stats-heading">Results</h2>
    <section class="stat-strip" aria-label="Results">
        <div class="stat">
            <span class="stat-value">{{ $results['marked'] }}</span>
            <span class="stat-label">Marks recorded</span>
        </div>
        <div class="stat">
            <span class="stat-value">{{ $percent($results['passRate']) }}</span>
            <span class="stat-label">Pass rate (grade point {{ number_format(config('grading.pass_point'), 2) }} or more)</span>
        </div>
        <div class="stat">
            <span class="stat-value">{{ $results['averageCgpa'] !== null ? number_format($results['averageCgpa'], 2) : '–' }}</span>
            <span class="stat-label">Average CGPA</span>
        </div>
        <div class="stat">
            <span class="stat-value">{{ $results['firstClass'] }}</span>
            <span class="stat-label">Students with CGPA 3.67 or more</span>
        </div>
    </section>

    <div class="grid-2">
        <section class="panel">
            <h3 class="panel-title">Grade distribution</h3>
            @php $maxGrade = max(max($results['grades']), 1); @endphp
            @if ($results['marked'] === 0)
                <p class="empty">No marks recorded yet.</p>
            @else
                <div class="columns" role="img" aria-label="Number of results for each grade">
                    @foreach ($results['grades'] as $grade => $total)
                        <div class="column" title="{{ $grade }}: {{ $total }} results">
                            <span class="column-value">{{ $total }}</span>
                            <span class="column-bar" style="height: {{ $total / $maxGrade * 100 }}%"></span>
                            <span class="column-label">{{ $grade }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
        <section class="panel">
            <h3 class="panel-title">Average CGPA by programme</h3>
            @forelse ($results['byProgramme'] as $row)
                <div class="hbar" title="{{ $row['name'] }}: average CGPA {{ number_format($row['average'], 2) }} across {{ $row['students'] }} students">
                    <span class="hbar-label">{{ $row['label'] }}</span>
                    <span class="hbar-track"><span class="hbar-fill" style="width: {{ $row['average'] / 4 * 100 }}%"></span></span>
                    <span class="hbar-value">{{ number_format($row['average'], 2) }}</span>
                </div>
            @empty
                <p class="empty">No marks recorded yet.</p>
            @endforelse
            <p class="muted small chart-note">Bars run from 0.00 to 4.00.</p>
        </section>
    </div>

    {{-- ---------- Fees ---------- --}}
    <h2 class="stats-heading">Fees and payments</h2>
    <section class="stat-strip" aria-label="Fees">
        <div class="stat">
            <span class="stat-value money">{{ Branding::money($finance['collected']) }}</span>
            <span class="stat-label">Collected in total</span>
        </div>
        <a href="{{ route('finance.index', ['owing' => 1]) }}" class="stat">
            <span class="stat-value money">{{ Branding::money($finance['outstanding']) }}</span>
            <span class="stat-label">Owed by {{ $finance['owing'] }} {{ str('student')->plural($finance['owing']) }}</span>
        </a>
        <div class="stat">
            <span class="stat-value">{{ $percent($finance['collectionRate']) }}</span>
            <span class="stat-label">Collection rate (collected of {{ Branding::money($finance['billed']) }} billed)</span>
        </div>
        <div class="stat">
            <span class="stat-value money">{{ Branding::money($finance['thisMonth']) }}</span>
            <span class="stat-label">Collected in {{ now()->format('F') }}</span>
        </div>
    </section>

    <div class="grid-2">
        <section class="panel">
            <h3 class="panel-title">Money collected, last six months ({{ Branding::currency() }})</h3>
            @php $maxMonth = max($finance['months']->max('amount'), 1); @endphp
            <div class="columns" role="img" aria-label="Money collected in each of the last six months">
                @foreach ($finance['months'] as $month)
                    <div class="column" title="{{ $month['title'] }}: {{ Branding::money($month['amount']) }}">
                        <span class="column-value">{{ $month['amount'] >= 1000 ? number_format($month['amount'] / 1000, 1).'k' : number_format($month['amount']) }}</span>
                        <span class="column-bar" style="height: {{ $month['amount'] / $maxMonth * 100 }}%"></span>
                        <span class="column-label">{{ $month['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </section>
        <section class="panel">
            <h3 class="panel-title">Still owed, by programme</h3>
            @php $maxOwed = max($finance['byProgramme']->max('amount'), 1); @endphp
            @forelse ($finance['byProgramme'] as $row)
                <div class="hbar wide-value" title="{{ $row['label'] }}: {{ Branding::money($row['amount']) }} owed by {{ $row['count'] }} students">
                    <span class="hbar-label">{{ $row['label'] }}</span>
                    <span class="hbar-track"><span class="hbar-fill" style="width: {{ $row['amount'] / $maxOwed * 100 }}%"></span></span>
                    <span class="hbar-value">{{ number_format($row['amount']) }}</span>
                </div>
            @empty
                <p class="empty">Nobody owes money.</p>
            @endforelse
        </section>
    </div>

    {{-- ---------- Timetable ---------- --}}
    <h2 class="stats-heading">Timetable{{ $term ? ', '.$term->name : '' }}</h2>
    <section class="stat-strip" aria-label="Timetable">
        <a href="{{ route('sessions.index') }}" class="stat">
            <span class="stat-value">{{ $timetable['sessions'] }}</span>
            <span class="stat-label">Sessions running</span>
        </a>
        <a href="{{ route('timetable.index') }}" class="stat">
            <span class="stat-value">{{ $timetable['classes'] }}</span>
            <span class="stat-label">Classes a week</span>
        </a>
        <div class="stat">
            <span class="stat-value">{{ rtrim(rtrim(number_format($timetable['hours'], 1), '0'), '.') }}</span>
            <span class="stat-label">Teaching hours a week</span>
        </div>
        <a href="{{ route('classrooms.index') }}" class="stat">
            <span class="stat-value">{{ $percent($timetable['utilisation']) }}</span>
            <span class="stat-label">Room use across {{ $timetable['rooms'] }} {{ str('room')->plural($timetable['rooms']) }}</span>
        </a>
    </section>

    @if ($timetable['busiest']->isNotEmpty())
        <section class="panel">
            <h3 class="panel-title">Busiest rooms (hours booked of {{ $timetable['perRoomMax'] }} a week)</h3>
            @foreach ($timetable['busiest'] as $room)
                <div class="hbar wide-label" title="{{ $room['name'] }}: {{ $room['hours'] }} hours a week">
                    <span class="hbar-label">{{ $room['label'] }}</span>
                    <span class="hbar-track"><span class="hbar-fill" style="width: {{ $room['hours'] / $timetable['perRoomMax'] * 100 }}%"></span></span>
                    <span class="hbar-value">{{ rtrim(rtrim(number_format($room['hours'], 1), '0'), '.') }}</span>
                </div>
            @endforeach
        </section>
    @endif
@endsection

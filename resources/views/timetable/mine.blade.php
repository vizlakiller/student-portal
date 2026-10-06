@extends('layouts.app')
@section('title', 'My timetable')
@section('subtitle', $term ? $term->name : 'No current term is set yet.')

@section('actions')
    @if ($slots->isNotEmpty())
        <button type="button" class="btn" onclick="window.print()">Print</button>
    @endif
@endsection

@section('content')
    @if ($slots->isEmpty())
        <div class="panel empty-state">
            <p>
                @can('teach')
                    You have no classes this term. Heads of department assign lecturers to sessions.
                @else
                    You have no classes this term yet. The registrar's office registers your subjects.
                @endcan
            </p>
        </div>
    @else
        @include('timetable._grid', ['days' => $days, 'show' => auth()->user()->can('teach') ? ['room'] : ['room', 'lecturers']])

        <section class="panel flush class-list">
            <div class="panel-header inset"><h2>Class list</h2></div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Day</th><th>Time</th><th>Subject</th><th>Classroom</th><th>Lecturers</th></tr></thead>
                    <tbody>
                        @foreach ($slots as $slot)
                            <tr>
                                <td class="strong">{{ $slot->dayName() }}</td>
                                <td class="num-left">{{ $slot->timeRange() }}</td>
                                <td>{{ $slot->classSession->label() }} <div class="muted small">{{ $slot->classSession->subject->name }}</div></td>
                                <td>{{ $slot->classroom->label() }}</td>
                                <td>{{ $slot->classSession->lecturers->pluck('name')->join(', ') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
@endsection

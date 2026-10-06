@extends('layouts.app')
@section('title', 'Classrooms')
@section('subtitle', $term ? 'Hours booked are for '.$term->name.', Monday to Friday.' : 'No current term is set yet.')

@section('actions')
    @can('manage-classrooms')
        <a href="{{ route('classrooms.create') }}" class="btn btn-primary">Add classroom</a>
    @endcan
@endsection

@section('content')
    <section class="panel flush">
        @if ($classrooms->isEmpty())
            <div class="empty-state">
                <p>No classrooms yet. Classes need a room before they can go on the timetable.</p>
                @can('manage-classrooms')
                    <a href="{{ route('classrooms.create') }}" class="btn btn-primary">Add the first classroom</a>
                @endcan
            </div>
        @else
            @php $weekHours = (\App\Support\Timetable::LAST_HOUR - \App\Support\Timetable::FIRST_HOUR) * 5; @endphp
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Code</th><th>Name</th><th>Type</th><th class="num">Seats</th>
                            <th>Hours booked</th><th><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($classrooms as $classroom)
                            @php
                                $hours = $classroom->slots->sum(fn ($slot) => \App\Support\Timetable::hours($slot));
                                $percent = round($hours / $weekHours * 100);
                            @endphp
                            <tr>
                                <td class="ref strong"><a href="{{ route('classrooms.show', $classroom) }}">{{ $classroom->code }}</a></td>
                                <td>
                                    {{ $classroom->name }}
                                    @if ($classroom->building)<div class="muted small">{{ $classroom->building }}</div>@endif
                                </td>
                                <td>{{ $classroom->type }}</td>
                                <td class="num">{{ $classroom->capacity }}</td>
                                <td>
                                    <span class="progress" title="{{ $percent }}% of the week">
                                        <span class="progress-fill" style="width: {{ min(100, $percent) }}%"></span>
                                    </span>
                                    <span class="muted small">{{ rtrim(rtrim(number_format($hours, 1), '0'), '.') }} h</span>
                                </td>
                                <td class="row-actions">
                                    <a href="{{ route('classrooms.show', $classroom) }}">Timetable</a>
                                    @can('manage-classrooms')
                                        <a href="{{ route('classrooms.edit', $classroom) }}">Edit</a>
                                        @unless ($classroom->term_slots_count)
                                            <form method="POST" action="{{ route('classrooms.destroy', $classroom) }}"
                                                  onsubmit="return confirm(@js('Delete classroom '.$classroom->code.'?'))">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="link-button danger">Delete</button>
                                            </form>
                                        @endunless
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection

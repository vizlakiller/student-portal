{{-- Weekly timetable grid. Needs $days from Timetable::byDay().
     Optional: $show = which details to print in each block (room, lecturers). --}}
@php
    use App\Support\Timetable;
    $show = $show ?? ['room', 'lecturers'];
    $hours = range(Timetable::FIRST_HOUR, Timetable::LAST_HOUR - 1);
@endphp

<div class="timetable-wrap">
    <div class="timetable" style="--rows: {{ count($hours) }}; --days: {{ count($days) }}">
        <div class="tt-corner"></div>
        @foreach ($days as $day => $items)
            <div class="tt-day-name">{{ Timetable::DAYS[$day] }}</div>
        @endforeach

        <div class="tt-times" aria-hidden="true">
            @foreach ($hours as $hour)
                <span>{{ sprintf('%02d:00', $hour) }}</span>
            @endforeach
        </div>

        @foreach ($days as $day => $items)
            <div class="tt-day" aria-label="{{ Timetable::DAYS[$day] }}">
                @forelse ($items as $item)
                    @php
                        $slot = $item['slot'];
                        $pos = Timetable::position($slot);
                        $session = $slot->classSession;
                    @endphp
                    <a class="tt-slot" href="{{ route('sessions.show', $session) }}"
                       style="top: {{ $pos['top'] }}%; height: {{ $pos['height'] }}%; left: calc({{ $item['lane'] }} / {{ $item['lanes'] }} * 100%); width: calc(100% / {{ $item['lanes'] }});"
                       title="{{ $session->label() }}, {{ $slot->timeRange() }}, {{ $slot->classroom->label() }}, {{ $session->lecturers->pluck('name')->join(', ') }}">
                        <strong>{{ $session->subject->code }}</strong>
                        <span class="tt-group">{{ $session->name }}</span>
                        <span class="tt-time">{{ $slot->timeRange() }}</span>
                        @if (in_array('room', $show))
                            <span class="tt-room">{{ $slot->classroom->code }}</span>
                        @endif
                        @if (in_array('lecturers', $show) && $session->lecturers->isNotEmpty())
                            <span class="tt-lecturer">{{ $session->lecturers->pluck('name')->join(', ') }}</span>
                        @endif
                    </a>
                @empty
                @endforelse
            </div>
        @endforeach
    </div>
</div>

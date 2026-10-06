{{-- A lecturer's sessions with how many students still need marks. Needs $sessions. --}}
<section class="panel flush">
    @if ($sessions->isEmpty())
        <div class="empty-state">
            <p>You don't lecture any sessions in this term. Heads of department assign lecturers to sessions.</p>
        </div>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Session</th>
                        <th>Class times</th>
                        <th class="num">Students</th>
                        <th>Marked</th>
                        <th><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sessions as $session)
                        @php $percent = $session->results_count ? round($session->marked_count / $session->results_count * 100) : 0; @endphp
                        <tr>
                            <td>
                                <span class="strong">{{ $session->label() }}</span>
                                <div class="muted small">{{ $session->subject->name }}@if ($session->lecturers->count() > 1), with {{ $session->lecturers->where('id', '!=', auth()->id())->pluck('name')->join(', ') }}@endif</div>
                            </td>
                            <td>
                                @forelse ($session->slots as $slot)
                                    <div class="small">{{ $slot->whenLabel() }}, {{ $slot->classroom->code }}</div>
                                @empty
                                    <span class="muted small">No class times yet</span>
                                @endforelse
                            </td>
                            <td class="num">{{ $session->results_count }}</td>
                            <td>
                                <span class="progress" title="{{ $percent }}% marked">
                                    <span class="progress-fill" style="width: {{ $percent }}%"></span>
                                </span>
                                <span class="muted small">{{ $session->marked_count }} of {{ $session->results_count }}</span>
                            </td>
                            <td class="row-actions">
                                <a href="{{ route('marks.edit', $session) }}">Enter marks</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>

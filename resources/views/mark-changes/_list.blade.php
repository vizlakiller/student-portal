{{-- Mark change requests with approve/reject buttons for the session lecturer. Needs $requests. --}}
<div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th>Student</th>
                <th>Subject</th>
                <th class="num">Change</th>
                <th>Reason</th>
                <th>Status</th>
                <th><span class="visually-hidden">Actions</span></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($requests as $change)
                <tr>
                    <td>
                        <span class="strong">{{ $change->result->student->name }}</span>
                        <div class="muted small">{{ $change->result->student->student_no }}</div>
                    </td>
                    <td>
                        {{ $change->result->subject->code }} {{ $change->result->classSession?->name }}
                        <div class="muted small">Lecturer: {{ $change->result->responsibleLecturers()->pluck('name')->join(', ') ?: 'none' }}</div>
                    </td>
                    <td class="num strong">{{ $change->old_marks ?? '–' }} to {{ $change->new_marks }}</td>
                    <td>
                        {{ $change->reason }}
                        <div class="muted small">By {{ $change->requester?->name ?? 'deleted user' }}, {{ $change->created_at->format('j M Y') }}</div>
                    </td>
                    <td>
                        <span class="decision decision-{{ $change->status }}">{{ ucfirst($change->status) }}</span>
                        @if (! $change->isPending())
                            <div class="muted small">
                                {{ $change->decider?->name }}, {{ $change->decided_at?->format('j M Y') }}
                                @if ($change->decision_note) <br>"{{ $change->decision_note }}" @endif
                            </div>
                        @endif
                    </td>
                    <td class="decision-actions">
                        @can('decide-mark-change', $change)
                            <form method="POST" action="{{ route('mark-changes.approve', $change) }}">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-small">Approve</button>
                            </form>
                            <form method="POST" action="{{ route('mark-changes.reject', $change) }}" class="reject-form">
                                @csrf
                                <input type="text" name="decision_note" placeholder="Reason (optional)" aria-label="Reason for rejecting" maxlength="500">
                                <button type="submit" class="btn btn-small">Reject</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

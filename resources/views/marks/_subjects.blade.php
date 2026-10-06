{{-- A teacher's subjects with how many students still need marks. Needs $subjects. --}}
<section class="panel flush">
    @if ($subjects->isEmpty())
        <div class="empty-state">
            <p>No subjects are assigned to you yet. The head of department assigns teachers to subjects.</p>
        </div>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Subject</th>
                        <th class="num">Students</th>
                        <th class="num">Marked</th>
                        <th>Progress</th>
                        <th><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($subjects as $subject)
                        @php $percent = $subject->results_count ? round($subject->marked_count / $subject->results_count * 100) : 0; @endphp
                        <tr>
                            <td class="ref strong">{{ $subject->code }}</td>
                            <td>{{ $subject->name }}</td>
                            <td class="num">{{ $subject->results_count }}</td>
                            <td class="num">{{ $subject->marked_count }}</td>
                            <td>
                                <span class="progress" title="{{ $percent }}% marked">
                                    <span class="progress-fill" style="width: {{ $percent }}%"></span>
                                </span>
                            </td>
                            <td class="row-actions">
                                <a href="{{ route('marks.edit', $subject) }}">Enter marks</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>

@extends('layouts.app')
@section('title', 'My results')
@section('subtitle', $student->programme->name)

@section('actions')
    <a href="{{ route('timetable.mine') }}" class="btn">My timetable</a>
    @if ($cgpa !== null)
        <a href="{{ route('my.transcript') }}" class="btn btn-primary" target="_blank">Print my transcript</a>
    @endif
@endsection

@section('content')
    <section class="record-card">
        <div class="record-main">
            <p class="record-no">{{ $student->student_no }}</p>
            <p class="record-programme">{{ $student->name }}</p>
            <p>
                @include('partials.status', ['status' => $student->status])
                <span class="muted">Intake {{ $student->intake_year }}, semester {{ $student->semester }}</span>
            </p>
            <p class="muted small">If any of your details are wrong, please contact the registrar's office.</p>
        </div>
        <div class="seal" aria-label="Cumulative GPA">
            <span class="seal-value">{{ $cgpa !== null ? number_format($cgpa, 2) : '–' }}</span>
            <span class="seal-label">CGPA</span>
            <span class="seal-meta">{{ $credits }} credit hours</span>
        </div>
    </section>

    @forelse ($semesters as $semester => $data)
        <div class="panel flush semester">
            <div class="semester-header">
                <h3>Semester {{ $semester }}</h3>
                <span>
                    @if ($data['gpa'] !== null)
                        GPA <strong>{{ number_format($data['gpa'], 2) }}</strong>
                    @else
                        Results not out yet
                    @endif
                </span>
            </div>
            <div class="table-wrap">
                <table class="table results-table">
                    <thead>
                        <tr><th>Code</th><th>Subject</th><th class="num">Credits</th><th class="num">Marks</th><th>Grade</th><th class="num">Point</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($data['results'] as $result)
                            <tr>
                                <td class="ref">{{ $result->subject->code }}</td>
                                <td>
                                    {{ $result->subject->name }}
                                    <div class="muted small">{{ $result->classSession ? $result->classSession->name.', ' : '' }}{{ $result->responsibleLecturers()->pluck('name')->join(', ') }}</div>
                                </td>
                                <td class="num">{{ $result->subject->credit_hours }}</td>
                                <td class="num">{{ $result->marks ?? '–' }}</td>
                                <td>
                                    @if ($result->isMarked())
                                        @include('partials.grade', ['grade' => $result->grade])
                                    @else
                                        <span class="pending">Not out yet</span>
                                    @endif
                                </td>
                                <td class="num">{{ $result->isMarked() ? number_format($result->grade_point, 2) : '–' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="panel empty-state">
            <p>You aren't registered for any subjects yet. The registrar's office registers your subjects each semester.</p>
        </div>
    @endforelse
@endsection

@extends('layouts.app')
@section('title', $student->name)
@section('back')<a href="{{ route('students.index') }}">Students</a>@endsection

@section('actions')
    @can('manage-finance')
        <a href="{{ route('finance.students.show', $student) }}" class="btn">Fees</a>
    @endcan
    @if ($record)
        <a href="{{ route('students.transcript', $student) }}" class="btn" target="_blank">Print transcript</a>
    @endif
    @can('edit-student-contact')
        <a href="{{ route('students.edit', $student) }}" class="btn">Edit details</a>
    @endcan
    @can('register-subjects')
        <a href="{{ route('registrations.create', $student) }}" class="btn btn-primary">Register subjects</a>
    @endcan
@endsection

@section('content')
    <section class="record-card">
        <div class="record-main">
            <p class="record-no">{{ $student->student_no }}</p>
            <p class="record-programme">{{ $student->programme->name }}</p>
            <p>
                @include('partials.status', ['status' => $student->status])
                <span class="muted">Intake {{ $student->intake_year }}, semester {{ $student->semester }}</span>
            </p>

            <dl class="details">
                <div><dt>Email</dt><dd><a href="mailto:{{ $student->email }}">{{ $student->email }}</a></dd></div>
                <div><dt>Phone</dt><dd>{{ $student->phone ?: 'Not recorded' }}</dd></div>
                <div><dt>Gender</dt><dd>{{ $student->gender }}</dd></div>
                <div><dt>Date of birth</dt><dd>{{ $student->date_of_birth?->format('j F Y') ?? 'Not recorded' }}</dd></div>
                <div class="span-2"><dt>Address</dt><dd>{{ $student->address ?: 'Not recorded' }}</dd></div>
            </dl>
        </div>

        @if ($record)
            <div class="seal" aria-label="Cumulative GPA">
                <span class="seal-value">{{ $record['cgpa'] !== null ? number_format($record['cgpa'], 2) : '–' }}</span>
                <span class="seal-label">CGPA</span>
                <span class="seal-meta">{{ $record['credits'] }} credit hours</span>
            </div>
        @endif
    </section>

    @can('reset-student-password')
        <section class="panel login-panel">
            <div>
                <h2>Student login</h2>
                @if ($student->user)
                    <p class="muted">
                        Logs in with <strong>{{ $student->user->email }}</strong>.
                        @if ($student->user->must_change_password)
                            Still using the first password (their student number).
                        @else
                            Has set their own password.
                        @endif
                    </p>
                @else
                    <p class="muted">No login yet. Resetting the password creates one.</p>
                @endif
            </div>
            <form method="POST" action="{{ route('students.reset-password', $student) }}"
                  onsubmit="return confirm(@js('Reset the password for '.$student->name.' to their student number?'))">
                @csrf
                <button type="submit" class="btn">Reset password</button>
            </form>
        </section>
    @endcan

    @if ($record)
        <section id="results">
            <div class="section-header">
                <h2>Subjects and results</h2>
            </div>

            @forelse ($record['semesters'] as $semester => $data)
                <div class="panel flush semester">
                    <div class="semester-header">
                        <h3>Semester {{ $semester }}</h3>
                        <span>
                            @if ($data['gpa'] !== null)
                                GPA <strong>{{ number_format($data['gpa'], 2) }}</strong>, {{ $data['credits'] }} credit hours
                            @else
                                No marks yet
                            @endif
                        </span>
                    </div>
                    <div class="table-wrap">
                        <table class="table results-table">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Subject</th>
                                    <th class="num">Credits</th>
                                    <th class="num">Marks</th>
                                    <th>Grade</th>
                                    <th class="num">Point</th>
                                    <th><span class="visually-hidden">Actions</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($data['results'] as $result)
                                    <tr>
                                        <td class="ref">{{ $result->subject->code }}</td>
                                        <td>
                                            {{ $result->subject->name }}
                                            <div class="muted small">{{ $result->subject->teacher?->name ?? 'No teacher assigned' }}</div>
                                        </td>
                                        <td class="num">{{ $result->subject->credit_hours }}</td>
                                        <td class="num">{{ $result->marks ?? '–' }}</td>
                                        <td>
                                            @if ($result->isMarked())
                                                @include('partials.grade', ['grade' => $result->grade])
                                            @else
                                                <span class="pending">Not marked</span>
                                            @endif
                                        </td>
                                        <td class="num">{{ $result->isMarked() ? number_format($result->grade_point, 2) : '–' }}</td>
                                        <td class="row-actions">
                                            @if ($result->pendingChange)
                                                <span class="pending" title="Waiting for the teacher to approve">
                                                    Change to {{ $result->pendingChange->new_marks }} waiting
                                                </span>
                                            @endif
                                            @can('request-mark-change', $result)
                                                <a href="{{ route('mark-changes.create', $result) }}">Request change</a>
                                            @endcan
                                            @can('edit-marks-directly')
                                                <a href="{{ route('results.edit', [$student, $result]) }}">Edit</a>
                                            @endcan
                                            @can('register-subjects')
                                                @if (! $result->isMarked() || auth()->user()->can('edit-marks-directly'))
                                                    <form method="POST" action="{{ route('results.destroy', [$student, $result]) }}"
                                                          onsubmit="return confirm(@js('Remove '.$result->subject->code.' from this student?'))">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="link-button danger">Remove</button>
                                                    </form>
                                                @endif
                                            @endcan
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="panel empty-state">
                    <p>{{ $student->name }} isn't registered for any subjects yet.</p>
                    @can('register-subjects')
                        <a href="{{ route('registrations.create', $student) }}" class="btn btn-primary">Register subjects</a>
                    @endcan
                </div>
            @endforelse
        </section>
    @endif
@endsection

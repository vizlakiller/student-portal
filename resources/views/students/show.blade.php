@extends('layouts.app')
@section('title', $student->name)
@section('back')<a href="{{ route('students.index') }}">Students</a>@endsection

@section('actions')
    <a href="{{ route('students.transcript', $student) }}" class="btn" target="_blank">Print transcript</a>
    <a href="{{ route('students.edit', $student) }}" class="btn">Edit details</a>
    <a href="{{ route('students.results.create', $student) }}" class="btn btn-primary">Add result</a>
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

        <div class="seal" aria-label="Cumulative GPA">
            <span class="seal-value">{{ $cgpa !== null ? number_format($cgpa, 2) : '–' }}</span>
            <span class="seal-label">CGPA</span>
            <span class="seal-meta">{{ $credits }} credit hours</span>
        </div>
    </section>

    <section id="results">
        <div class="section-header">
            <h2>Results</h2>
        </div>

        @forelse ($semesters as $semester => $data)
            <div class="panel flush semester">
                <div class="semester-header">
                    <h3>Semester {{ $semester }}</h3>
                    <span>GPA <strong>{{ number_format($data['gpa'], 2) }}</strong>, {{ $data['credits'] }} credit hours</span>
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
                                    <td>{{ $result->subject->name }}</td>
                                    <td class="num">{{ $result->subject->credit_hours }}</td>
                                    <td class="num">{{ $result->marks }}</td>
                                    <td>@include('partials.grade', ['grade' => $result->grade])</td>
                                    <td class="num">{{ number_format($result->grade_point, 2) }}</td>
                                    <td class="row-actions">
                                        <a href="{{ route('students.results.edit', [$student, $result]) }}">Edit</a>
                                        <form method="POST" action="{{ route('students.results.destroy', [$student, $result]) }}"
                                              onsubmit="return confirm(@js('Delete the '.$result->subject->code.' result?'))">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="link-button danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="panel empty-state">
                <p>No results recorded for {{ $student->name }} yet.</p>
                <a href="{{ route('students.results.create', $student) }}" class="btn btn-primary">Add the first result</a>
            </div>
        @endforelse
    </section>
@endsection

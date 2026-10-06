@extends('layouts.app')
@section('title', 'Dashboard')
@section('subtitle', 'Welcome back, '.auth()->user()->name.'.')

@section('actions')
    @can('create-students')
        <a href="{{ route('students.create') }}" class="btn btn-primary">Add student</a>
    @endcan
@endsection

@section('content')
    <section class="stat-strip" aria-label="Summary">
        <a href="{{ route('students.index') }}" class="stat">
            <span class="stat-value">{{ $stats['students'] }}</span>
            <span class="stat-label">Students</span>
        </a>
        <a href="{{ route('students.index', ['status' => 'Active']) }}" class="stat">
            <span class="stat-value">{{ $stats['active'] }}</span>
            <span class="stat-label">Currently active</span>
        </a>
        <a href="{{ auth()->user()->can('view-programmes') ? route('programmes.index') : '#' }}" class="stat">
            <span class="stat-value">{{ $stats['programmes'] }}</span>
            <span class="stat-label">Programmes</span>
        </a>
        <a href="{{ auth()->user()->can('view-subjects') ? route('subjects.index') : '#' }}" class="stat">
            <span class="stat-value">{{ $stats['subjects'] }}</span>
            <span class="stat-label">Subjects</span>
        </a>
    </section>

    <div class="grid-2">
        <section class="panel">
            <h2>Students by programme</h2>
            @php $max = max($programmes->max('students_count'), 1); @endphp
            @forelse ($programmes as $programme)
                <a class="hbar" href="{{ route('students.index', ['programme' => $programme->id]) }}"
                   title="{{ $programme->name }}: {{ $programme->students_count }} students">
                    <span class="hbar-label">{{ $programme->code }}</span>
                    <span class="hbar-track">
                        <span class="hbar-fill" style="width: {{ $programme->students_count / $max * 100 }}%"></span>
                    </span>
                    <span class="hbar-value">{{ $programme->students_count }}</span>
                </a>
            @empty
                <p class="empty">No programmes yet.</p>
            @endforelse
        </section>

        <section class="panel">
            <h2>Students by status</h2>
            <ul class="status-list">
                @foreach ($statuses as $status => $total)
                    <li>
                        <a href="{{ route('students.index', ['status' => $status]) }}">
                            @include('partials.status', ['status' => $status])
                            <span class="num">{{ $total }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>

        @if ($canSeeResults)
        <section class="panel">
            <h2>Grade distribution</h2>
            @php $maxGrade = max(max($grades), 1); @endphp
            @if (array_sum($grades) === 0)
                <p class="empty">No results recorded yet. Grades appear here once teachers enter marks.</p>
            @else
                <div class="columns" role="img" aria-label="Number of results for each grade">
                    @foreach ($grades as $grade => $total)
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
            <h2>Top students by CGPA</h2>
            @if ($topStudents->isEmpty())
                <p class="empty">No marks recorded yet.</p>
            @else
                <table class="table compact">
                    <tbody>
                        @foreach ($topStudents as $student)
                            <tr>
                                <td>
                                    <a href="{{ route('students.show', $student) }}" class="strong">{{ $student->name }}</a>
                                    <div class="muted small">{{ $student->programme->code }}, semester {{ $student->semester }}</div>
                                </td>
                                <td class="num strong">{{ number_format($student->cgpa(), 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
        @endif
    </div>

    <section class="panel">
        <div class="panel-header">
            <h2>Recently added</h2>
            <a href="{{ route('students.index') }}">View all students</a>
        </div>
        @if ($recentStudents->isEmpty())
            <p class="empty">No students yet.</p>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>Student no.</th><th>Name</th><th>Programme</th><th>Intake</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($recentStudents as $student)
                            <tr>
                                <td class="ref">{{ $student->student_no }}</td>
                                <td><a href="{{ route('students.show', $student) }}">{{ $student->name }}</a></td>
                                <td>{{ $student->programme->code }}</td>
                                <td class="num">{{ $student->intake_year }}</td>
                                <td>@include('partials.status', ['status' => $student->status])</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection

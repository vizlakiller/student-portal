@extends('layouts.app')
@section('title', 'Students')
@section('subtitle', $students->total().' '.str('student')->plural($students->total()).(array_filter($filters) ? ' match your filters' : ' in total'))

@section('actions')
    <a href="{{ route('students.export', request()->query()) }}" class="btn">Export to Excel (CSV)</a>
    @can('create-students')
        <a href="{{ route('students.create') }}" class="btn btn-primary">Add student</a>
    @endcan
@endsection

@section('content')
    <form method="GET" action="{{ route('students.index') }}" class="filter-bar" role="search">
        <div class="field grow">
            <label for="search">Search</label>
            <input id="search" type="search" name="search" value="{{ $filters['search'] ?? '' }}"
                   placeholder="Name, student number or email">
        </div>
        <div class="field">
            <label for="programme">Programme</label>
            <select id="programme" name="programme">
                <option value="">All programmes</option>
                @foreach ($programmes as $programme)
                    <option value="{{ $programme->id }}" @selected(($filters['programme'] ?? '') == $programme->id)>
                        {{ $programme->code }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">All statuses</option>
                @foreach (\App\Models\Student::STATUSES as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-buttons">
            <button type="submit" class="btn btn-primary">Apply</button>
            @if (array_filter($filters))
                <a href="{{ route('students.index') }}" class="btn btn-quiet">Clear</a>
            @endif
        </div>
    </form>

    <section class="panel flush">
        @if ($students->isEmpty())
            <div class="empty-state">
                @if (array_filter($filters))
                    <p>No students match these filters.</p>
                    <a href="{{ route('students.index') }}" class="btn">Clear filters</a>
                @else
                    <p>No students yet.</p>
                    @can('create-students')
                        <a href="{{ route('students.create') }}" class="btn btn-primary">Add the first student</a>
                    @endcan
                @endif
            </div>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Student no.</th>
                            <th>Name</th>
                            <th>Programme</th>
                            <th class="num">Semester</th>
                            @can('view-results')
                                <th class="num">CGPA</th>
                            @endcan
                            <th>Status</th>
                            <th><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($students as $student)
                            <tr>
                                <td class="ref">{{ $student->student_no }}</td>
                                <td>
                                    <a href="{{ route('students.show', $student) }}" class="strong">{{ $student->name }}</a>
                                    <div class="muted small">{{ $student->email }}</div>
                                </td>
                                <td title="{{ $student->programme->name }}">{{ $student->programme->code }}</td>
                                <td class="num">{{ $student->semester }}</td>
                                @can('view-results')
                                    @php $cgpa = $student->cgpa(); @endphp
                                    <td class="num">{{ $cgpa !== null ? number_format($cgpa, 2) : '–' }}</td>
                                @endcan
                                <td>@include('partials.status', ['status' => $student->status])</td>
                                <td class="row-actions">
                                    @can('edit-student-contact')
                                        <a href="{{ route('students.edit', $student) }}">Edit</a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{ $students->links() }}
@endsection

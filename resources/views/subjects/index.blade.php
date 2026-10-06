@extends('layouts.app')
@section('title', 'Subjects')
@section('subtitle', 'A subject can have several lecturers. Each term, students are registered into one of its sessions.')

@section('actions')
    @can('manage-subjects')
        <a href="{{ route('subjects.create') }}" class="btn btn-primary">Add subject</a>
    @endcan
@endsection

@section('content')
    <form method="GET" action="{{ route('subjects.index') }}" class="filter-bar">
        <div class="field">
            <label for="department">Department</label>
            <select id="department" name="department" onchange="this.form.submit()">
                <option value="">All departments</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected($departmentId === $department->id)>{{ $department->code }} {{ $department->name }}</option>
                @endforeach
            </select>
        </div>
        <noscript><button type="submit" class="btn">Show</button></noscript>
    </form>

    <section class="panel flush">
        @if ($subjects->isEmpty())
            <div class="empty-state">
                <p>No subjects{{ $departmentId ? ' in this department' : '' }} yet.</p>
                @can('manage-subjects')
                    <a href="{{ route('subjects.create') }}" class="btn btn-primary">Add a subject</a>
                @endcan
            </div>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Lecturers</th>
                            <th class="num">Credits</th>
                            <th class="num">Students</th>
                            <th><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($subjects as $subject)
                            <tr>
                                <td class="ref strong">{{ $subject->code }}</td>
                                <td>{{ $subject->name }}</td>
                                <td>{{ $subject->department?->code ?? '–' }}</td>
                                <td>
                                    @forelse ($subject->lecturers as $lecturer)
                                        @can('view-lecturers')
                                            <a href="{{ route('lecturers.show', $lecturer) }}">{{ $lecturer->name }}</a>@if (! $loop->last), @endif
                                        @else
                                            {{ $lecturer->name }}@if (! $loop->last), @endif
                                        @endcan
                                    @empty
                                        <span class="pending">No lecturer</span>
                                    @endforelse
                                </td>
                                <td class="num">{{ $subject->credit_hours }}</td>
                                <td class="num">{{ $subject->results_count }}</td>
                                <td class="row-actions">
                                    @can('manage-subject', $subject)
                                        <a href="{{ route('subjects.edit', $subject) }}">Edit</a>
                                        <form method="POST" action="{{ route('subjects.destroy', $subject) }}"
                                              onsubmit="return confirm(@js('Delete subject '.$subject->code.'?'))">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="link-button danger">Delete</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{ $subjects->links() }}
@endsection

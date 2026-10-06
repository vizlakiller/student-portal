@extends('layouts.app')
@section('title', 'Subjects')
@section('subtitle', 'Subjects students register for. Each subject has one teacher who enters its marks.')

@section('actions')
    @can('manage-subjects')
        <a href="{{ route('subjects.create') }}" class="btn btn-primary">Add subject</a>
    @endcan
@endsection

@section('content')
    <section class="panel flush">
        @if ($subjects->isEmpty())
            <div class="empty-state">
                <p>No subjects yet.</p>
                @can('manage-subjects')
                    <a href="{{ route('subjects.create') }}" class="btn btn-primary">Add the first subject</a>
                @endcan
            </div>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Teacher</th>
                            <th class="num">Credit hours</th>
                            <th class="num">Students</th>
                            <th><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($subjects as $subject)
                            <tr>
                                <td class="ref strong">{{ $subject->code }}</td>
                                <td>{{ $subject->name }}</td>
                                <td>
                                    @if ($subject->teacher)
                                        @can('view-teachers')
                                            <a href="{{ route('teachers.show', $subject->teacher) }}">{{ $subject->teacher->name }}</a>
                                        @else
                                            {{ $subject->teacher->name }}
                                        @endcan
                                    @else
                                        <span class="pending">Not assigned</span>
                                    @endif
                                </td>
                                <td class="num">{{ $subject->credit_hours }}</td>
                                <td class="num">{{ $subject->results_count }}</td>
                                <td class="row-actions">
                                    @can('manage-subjects')
                                    <a href="{{ route('subjects.edit', $subject) }}">{{ auth()->user()->can('assign-teachers') ? 'Edit or assign teacher' : 'Edit' }}</a>
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

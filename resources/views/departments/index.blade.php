@extends('layouts.app')
@section('title', 'Departments')
@section('subtitle', 'Each department has one head. A head of department manages subjects, sessions and the timetable for the departments they head.')

@section('actions')
    @can('manage-departments')
        <a href="{{ route('departments.create') }}" class="btn btn-primary">Add department</a>
    @endcan
@endsection

@section('content')
    <section class="panel flush">
        @if ($departments->isEmpty())
            <div class="empty-state"><p>No departments yet.</p></div>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>Code</th><th>Department</th><th>Head of department</th><th class="num">Programmes</th><th class="num">Subjects</th><th><span class="visually-hidden">Actions</span></th></tr>
                    </thead>
                    <tbody>
                        @foreach ($departments as $department)
                            <tr>
                                <td class="ref strong">{{ $department->code }}</td>
                                <td>{{ $department->name }}</td>
                                <td>{!! $department->hod ? e($department->hod->name) : '<span class="pending">No head yet</span>' !!}</td>
                                <td class="num">{{ $department->programmes_count }}</td>
                                <td class="num">
                                    @can('view-subjects')
                                        <a href="{{ route('subjects.index', ['department' => $department->id]) }}">{{ $department->subjects_count }}</a>
                                    @else
                                        {{ $department->subjects_count }}
                                    @endcan
                                </td>
                                <td class="row-actions">
                                    @can('manage-departments')
                                        <a href="{{ route('departments.edit', $department) }}">Edit</a>
                                        @unless ($department->programmes_count || $department->subjects_count)
                                            <form method="POST" action="{{ route('departments.destroy', $department) }}"
                                                  onsubmit="return confirm(@js('Delete department '.$department->code.'?'))">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="link-button danger">Delete</button>
                                            </form>
                                        @endunless
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection

@extends('layouts.app')
@section('title', 'Programmes')
@section('subtitle', 'The courses students enrol in.')

@section('actions')
    <a href="{{ route('programmes.create') }}" class="btn btn-primary">Add programme</a>
@endsection

@section('content')
    <section class="panel flush">
        @if ($programmes->isEmpty())
            <div class="empty-state">
                <p>No programmes yet. Students need a programme before you can add them.</p>
                <a href="{{ route('programmes.create') }}" class="btn btn-primary">Add the first programme</a>
            </div>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Level</th>
                            <th class="num">Students</th>
                            <th><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($programmes as $programme)
                            <tr>
                                <td class="ref strong">{{ $programme->code }}</td>
                                <td>{{ $programme->name }}</td>
                                <td>{{ $programme->level }}</td>
                                <td class="num">
                                    <a href="{{ route('students.index', ['programme' => $programme->id]) }}">{{ $programme->students_count }}</a>
                                </td>
                                <td class="row-actions">
                                    <a href="{{ route('programmes.edit', $programme) }}">Edit</a>
                                    <form method="POST" action="{{ route('programmes.destroy', $programme) }}"
                                          onsubmit="return confirm(@js('Delete programme '.$programme->code.'?'))">
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
        @endif
    </section>
@endsection

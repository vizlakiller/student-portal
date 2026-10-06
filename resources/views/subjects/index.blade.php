@extends('layouts.app')
@section('title', 'Subjects')
@section('subtitle', 'Subjects that results can be recorded for.')

@section('actions')
    <a href="{{ route('subjects.create') }}" class="btn btn-primary">Add subject</a>
@endsection

@section('content')
    <section class="panel flush">
        @if ($subjects->isEmpty())
            <div class="empty-state">
                <p>No subjects yet. Add subjects so you can record student results.</p>
                <a href="{{ route('subjects.create') }}" class="btn btn-primary">Add the first subject</a>
            </div>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th class="num">Credit hours</th>
                            <th class="num">Results recorded</th>
                            <th><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($subjects as $subject)
                            <tr>
                                <td class="ref strong">{{ $subject->code }}</td>
                                <td>{{ $subject->name }}</td>
                                <td class="num">{{ $subject->credit_hours }}</td>
                                <td class="num">{{ $subject->results_count }}</td>
                                <td class="row-actions">
                                    <a href="{{ route('subjects.edit', $subject) }}">Edit</a>
                                    <form method="POST" action="{{ route('subjects.destroy', $subject) }}"
                                          onsubmit="return confirm(@js('Delete subject '.$subject->code.'?'))">
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

    {{ $subjects->links() }}
@endsection

@extends('layouts.app')
@section('title', 'Edit student')
@section('subtitle', $student->name.' ('.$student->student_no.')')
@section('back')<a href="{{ route('students.show', $student) }}">Back to {{ $student->name }}</a>@endsection

@section('content')
    <form method="POST" action="{{ route('students.update', $student) }}" class="panel form">
        @method('PUT')
        @include('students._form')

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save changes</button>
            <a href="{{ route('students.show', $student) }}" class="btn btn-quiet">Cancel</a>
        </div>
    </form>

    @can('delete-students')
    <section class="panel danger-zone">
        <h2>Delete this student</h2>
        <p>This permanently removes {{ $student->name }}, their login, results, charges and payments.</p>
        <form method="POST" action="{{ route('students.destroy', $student) }}"
              onsubmit="return confirm(@js('Delete '.$student->name.' and all their results? This cannot be undone.'))">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Delete student</button>
        </form>
    </section>
    @endcan
@endsection

@extends('layouts.app')
@section('title', 'Add student')
@section('back')<a href="{{ route('students.index') }}">Students</a>@endsection

@section('content')
    @if ($programmes->isEmpty())
        <div class="alert alert-error">
            Add a programme before adding students. <a href="{{ route('programmes.create') }}">Add a programme</a>
        </div>
    @else
        <form method="POST" action="{{ route('students.store') }}" class="panel form">
            @include('students._form')

            <p class="muted">A login is created automatically: the student's email, with their student number as the first password. They must choose a new password the first time they log in.</p>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Add student</button>
                <a href="{{ route('students.index') }}" class="btn btn-quiet">Cancel</a>
            </div>
        </form>
    @endif
@endsection

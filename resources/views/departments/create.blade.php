@extends('layouts.app')
@section('title', 'Add department')
@section('back')<a href="{{ route('departments.index') }}">Departments</a>@endsection

@section('content')
    <form method="POST" action="{{ route('departments.store') }}" class="panel form narrow">
        @include('departments._form')
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Add department</button>
            <a href="{{ route('departments.index') }}" class="btn btn-quiet">Cancel</a>
        </div>
    </form>
@endsection

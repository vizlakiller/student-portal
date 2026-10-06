@extends('layouts.app')
@section('title', 'Add subject')
@section('back')<a href="{{ route('subjects.index') }}">Subjects</a>@endsection

@section('content')
    <form method="POST" action="{{ route('subjects.store') }}" class="panel form narrow">
        @include('subjects._form')
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Add subject</button>
            <a href="{{ route('subjects.index') }}" class="btn btn-quiet">Cancel</a>
        </div>
    </form>
@endsection

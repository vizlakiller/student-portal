@extends('layouts.app')
@section('title', 'Add classroom')
@section('back')<a href="{{ route('classrooms.index') }}">Classrooms</a>@endsection

@section('content')
    <form method="POST" action="{{ route('classrooms.store') }}" class="panel form narrow">
        @include('classrooms._form')
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Add classroom</button>
            <a href="{{ route('classrooms.index') }}" class="btn btn-quiet">Cancel</a>
        </div>
    </form>
@endsection

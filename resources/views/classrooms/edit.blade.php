@extends('layouts.app')
@section('title', 'Edit classroom')
@section('subtitle', $classroom->code.' '.$classroom->name)
@section('back')<a href="{{ route('classrooms.index') }}">Classrooms</a>@endsection

@section('content')
    <form method="POST" action="{{ route('classrooms.update', $classroom) }}" class="panel form narrow">
        @method('PUT')
        @include('classrooms._form')
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save changes</button>
            <a href="{{ route('classrooms.index') }}" class="btn btn-quiet">Cancel</a>
        </div>
    </form>
@endsection

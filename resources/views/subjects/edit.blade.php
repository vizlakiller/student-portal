@extends('layouts.app')
@section('title', 'Edit subject')
@section('subtitle', $subject->code.' '.$subject->name)
@section('back')<a href="{{ route('subjects.index') }}">Subjects</a>@endsection

@section('content')
    <form method="POST" action="{{ route('subjects.update', $subject) }}" class="panel form narrow">
        @method('PUT')
        @include('subjects._form')
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save changes</button>
            <a href="{{ route('subjects.index') }}" class="btn btn-quiet">Cancel</a>
        </div>
    </form>
@endsection

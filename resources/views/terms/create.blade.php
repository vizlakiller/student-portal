@extends('layouts.app')
@section('title', 'Add term')
@section('back')<a href="{{ route('terms.index') }}">Terms</a>@endsection

@section('content')
    <form method="POST" action="{{ route('terms.store') }}" class="panel form narrow">
        @include('terms._form')
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Add term</button>
            <a href="{{ route('terms.index') }}" class="btn btn-quiet">Cancel</a>
        </div>
    </form>
@endsection

@extends('layouts.app')
@section('title', 'Edit term')
@section('subtitle', $term->name)
@section('back')<a href="{{ route('terms.index') }}">Terms</a>@endsection

@section('content')
    <form method="POST" action="{{ route('terms.update', $term) }}" class="panel form narrow">
        @method('PUT')
        @include('terms._form')
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save changes</button>
            <a href="{{ route('terms.index') }}" class="btn btn-quiet">Cancel</a>
        </div>
    </form>
@endsection

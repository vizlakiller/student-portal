@extends('layouts.app')
@section('title', 'Add programme')
@section('back')<a href="{{ route('programmes.index') }}">Programmes</a>@endsection

@section('content')
    <form method="POST" action="{{ route('programmes.store') }}" class="panel form narrow">
        @include('programmes._form')
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Add programme</button>
            <a href="{{ route('programmes.index') }}" class="btn btn-quiet">Cancel</a>
        </div>
    </form>
@endsection

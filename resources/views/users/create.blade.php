@extends('layouts.app')
@section('title', 'Add staff account')
@section('back')<a href="{{ route('users.index') }}">User accounts</a>@endsection

@section('content')
    <form method="POST" action="{{ route('users.store') }}" class="panel form narrow">
        @include('users._form')
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Add account</button>
            <a href="{{ route('users.index') }}" class="btn btn-quiet">Cancel</a>
        </div>
    </form>
@endsection

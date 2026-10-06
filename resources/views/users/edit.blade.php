@extends('layouts.app')
@section('title', 'Edit account')
@section('subtitle', $user->name)
@section('back')<a href="{{ route('users.index') }}">User accounts</a>@endsection

@section('content')
    <form method="POST" action="{{ route('users.update', $user) }}" class="panel form narrow">
        @method('PUT')
        @include('users._form')
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save changes</button>
            <a href="{{ route('users.index') }}" class="btn btn-quiet">Cancel</a>
        </div>
    </form>
@endsection

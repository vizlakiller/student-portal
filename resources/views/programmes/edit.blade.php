@extends('layouts.app')
@section('title', 'Edit programme')
@section('subtitle', $programme->code.' '.$programme->name)
@section('back')<a href="{{ route('programmes.index') }}">Programmes</a>@endsection

@section('content')
    <form method="POST" action="{{ route('programmes.update', $programme) }}" class="panel form narrow">
        @method('PUT')
        @include('programmes._form')
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save changes</button>
            <a href="{{ route('programmes.index') }}" class="btn btn-quiet">Cancel</a>
        </div>
    </form>
@endsection

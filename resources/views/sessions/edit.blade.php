@extends('layouts.app')
@section('title', 'Edit session')
@section('subtitle', $session->label().', '.$session->term->name)
@section('back')<a href="{{ route('sessions.show', $session) }}">Back to {{ $session->label() }}</a>@endsection

@section('content')
    <form method="POST" action="{{ route('sessions.update', $session) }}" class="panel form narrow">
        @csrf
        @method('PUT')
        <div class="form-grid">
            <x-input label="Session name" name="name" :value="$session->name" required maxlength="60" />
            <x-input label="Maximum students" name="capacity" type="number" min="1" max="1000" :value="$session->capacity"
                     hint="Leave empty for no limit." />
        </div>

        @include('sessions._lecturers', ['subject' => $subject, 'selected' => old('lecturer_ids', $session->lecturers->pluck('id')->all())])

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save changes</button>
            <a href="{{ route('sessions.show', $session) }}" class="btn btn-quiet">Cancel</a>
        </div>
    </form>
@endsection

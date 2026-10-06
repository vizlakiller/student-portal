@extends('layouts.app')
@section('title', 'My sessions')
@section('subtitle', 'Sessions you lecture. Open one to enter marks for its students.')

@section('content')
    <form method="GET" action="{{ route('marks.index') }}" class="filter-bar">
        <div class="field">
            <label for="term">Term</label>
            <select id="term" name="term" onchange="this.form.submit()">
                @foreach ($terms as $option)
                    <option value="{{ $option->id }}" @selected($term?->id === $option->id)>{{ $option->name }}{{ $option->is_current ? ' (current)' : '' }}</option>
                @endforeach
            </select>
        </div>
        <noscript><button type="submit" class="btn">Show</button></noscript>
    </form>

    @include('marks._sessions')
@endsection

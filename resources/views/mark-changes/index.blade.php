@extends('layouts.app')
@section('title', 'Mark changes')
@section('subtitle', auth()->user()->hasRole('teacher')
    ? 'Changes the head of department has asked for in your subjects. Marks change only when you approve.'
    : 'Changes you have asked for. Each one waits for the subject teacher to approve it.')

@section('content')
    <section class="panel flush">
        @if ($requests->isEmpty())
            <div class="empty-state">
                <p>No mark change requests yet.</p>
            </div>
        @else
            @include('mark-changes._list')
        @endif
    </section>

    {{ $requests->links() }}
@endsection

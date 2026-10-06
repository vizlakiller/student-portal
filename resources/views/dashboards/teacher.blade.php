@extends('layouts.app')
@section('title', 'Dashboard')
@section('subtitle', 'Welcome back, '.auth()->user()->name.'.')

@section('content')
    @if ($pending->isNotEmpty())
        <section class="panel flush attention">
            <div class="panel-header inset">
                <h2>Waiting for your approval</h2>
                <a href="{{ route('mark-changes.index') }}">All mark changes</a>
            </div>
            @include('mark-changes._list', ['requests' => $pending])
        </section>
    @endif

    <div class="section-header">
        <h2>My subjects</h2>
    </div>
    @include('marks._subjects')
@endsection

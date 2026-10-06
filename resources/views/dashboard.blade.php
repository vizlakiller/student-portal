@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
    <h2>Welcome, {{ auth()->user()->name }}</h2>
    <p>You are logged in as {{ auth()->user()->email }}.</p>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Logout</button>
    </form>
@endsection
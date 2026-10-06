@extends('layouts.guest')
@section('title', 'Log in')

@section('content')
    <h1>Log in</h1>
    <p class="muted">Use the account your administrator gave you.</p>

    <form method="POST" action="{{ route('login') }}" class="stack">
        @csrf

        <x-input label="Email" name="email" type="email" required autofocus autocomplete="username" />
        <x-input label="Password" name="password" type="password" required autocomplete="current-password" />

        <label class="checkbox">
            <input type="checkbox" name="remember" value="1"> Keep me logged in
        </label>

        <button type="submit" class="btn btn-primary btn-block">Log in</button>
    </form>
@endsection

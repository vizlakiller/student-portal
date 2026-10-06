@extends('layouts.app')
@section('title', 'Login')

@section('content')
    <h2>Login</h2>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <label>Email
            <input type="email" name="email" value="{{ old('email') }}" required autofocus>
        </label>
        @error('email') <div class="error">{{ $message }}</div> @enderror

        <label>Password
            <input type="password" name="password" required>
        </label>

        <label><input type="checkbox" name="remember"> Remember me</label>

        <button type="submit">Login</button>
    </form>

    <p>No account? <a href="{{ route('register') }}">Register</a></p>
@endsection
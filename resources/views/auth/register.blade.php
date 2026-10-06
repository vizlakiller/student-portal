@extends('layouts.app')
@section('title', 'Register')

@section('content')
    <h2>Register</h2>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <label>Full name
            <input type="text" name="name" value="{{ old('name') }}" required autofocus>
        </label>
        @error('name') <div class="error">{{ $message }}</div> @enderror

        <label>Email
            <input type="email" name="email" value="{{ old('email') }}" required>
        </label>
        @error('email') <div class="error">{{ $message }}</div> @enderror

        <label>Password
            <input type="password" name="password" required>
        </label>
        @error('password') <div class="error">{{ $message }}</div> @enderror

        <label>Confirm password
            <input type="password" name="password_confirmation" required>
        </label>

        <button type="submit">Create account</button>
    </form>

    <p>Already registered? <a href="{{ route('login') }}">Login</a></p>
@endsection
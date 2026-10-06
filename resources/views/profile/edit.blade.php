@extends('layouts.app')
@section('title', 'My profile')
@section('subtitle', auth()->user()->roleLabel())

@section('content')
    @if ($user->must_change_password)
        <div class="alert alert-error">This is your first login. Choose your own password to start using the portal. Your current password is your student number.</div>
    @endif

    @unless ($user->hasRole('student'))
        <form method="POST" action="{{ route('profile.update') }}" class="panel form narrow">
            @csrf
            @method('PUT')
            <h2>Your details</h2>
            <x-input label="Name" name="name" :value="$user->name" required autocomplete="name" />
            <x-input label="Email" name="email" type="email" :value="$user->email" required autocomplete="email" />
            <x-input label="Phone" name="phone" :value="$user->phone" autocomplete="tel" />
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save details</button>
            </div>
        </form>
    @endunless

    <form method="POST" action="{{ route('profile.password') }}" class="panel form narrow">
        @csrf
        @method('PUT')
        <h2>{{ $user->must_change_password ? 'Choose your password' : 'Change password' }}</h2>
        <x-input label="Current password" name="current_password" type="password" required autocomplete="current-password" />
        <x-input label="New password" name="password" type="password" required autocomplete="new-password" hint="At least 8 characters." />
        <x-input label="Confirm new password" name="password_confirmation" type="password" required autocomplete="new-password" />
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">{{ $user->must_change_password ? 'Save password and continue' : 'Change password' }}</button>
        </div>
    </form>
@endsection

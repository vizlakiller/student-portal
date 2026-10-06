@extends('layouts.app')
@section('title', 'My profile')

@section('content')
    <form method="POST" action="{{ route('profile.update') }}" class="panel form narrow">
        @csrf
        @method('PUT')
        <h2>Your details</h2>
        <x-input label="Name" name="name" :value="$user->name" required autocomplete="name" />
        <x-input label="Email" name="email" type="email" :value="$user->email" required autocomplete="email" />
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save details</button>
        </div>
    </form>

    <form method="POST" action="{{ route('profile.password') }}" class="panel form narrow">
        @csrf
        @method('PUT')
        <h2>Change password</h2>
        <x-input label="Current password" name="current_password" type="password" required autocomplete="current-password" />
        <x-input label="New password" name="password" type="password" required autocomplete="new-password" hint="At least 8 characters." />
        <x-input label="Confirm new password" name="password_confirmation" type="password" required autocomplete="new-password" />
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Change password</button>
        </div>
    </form>
@endsection

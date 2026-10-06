@extends('layouts.app')
@section('title', 'Staff accounts')
@section('subtitle', 'People who can log in. Admins can also manage these accounts.')

@section('actions')
    <a href="{{ route('users.create') }}" class="btn btn-primary">Add account</a>
@endsection

@section('content')
    <section class="panel flush">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Added</th>
                        <th><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td class="strong">
                                {{ $user->name }}
                                @if ($user->is(auth()->user())) <span class="muted small">(you)</span> @endif
                            </td>
                            <td>{{ $user->email }}</td>
                            <td><span class="role role-{{ $user->role }}">{{ ucfirst($user->role) }}</span></td>
                            <td>{{ $user->created_at?->format('j M Y') }}</td>
                            <td class="row-actions">
                                <a href="{{ route('users.edit', $user) }}">Edit</a>
                                @unless ($user->is(auth()->user()))
                                    <form method="POST" action="{{ route('users.destroy', $user) }}"
                                          onsubmit="return confirm(@js('Delete the account for '.$user->name.'?'))">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="link-button danger">Delete</button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection

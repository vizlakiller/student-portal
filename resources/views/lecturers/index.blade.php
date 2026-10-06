@extends('layouts.app')
@section('title', 'Lecturers')
@section('subtitle', 'Lecturer accounts are added by the super admin. Heads of department assign them to subjects and sessions.')

@section('content')
    <section class="panel flush">
        @if ($lecturers->isEmpty())
            <div class="empty-state"><p>No lecturers yet.</p></div>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>Staff no.</th><th>Name</th><th>Email</th><th>Phone</th><th>Subjects</th><th class="num">Sessions{{ $term ? ' this term' : '' }}</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($lecturers as $lecturer)
                            <tr>
                                <td class="ref">{{ $lecturer->staff_no ?? '–' }}</td>
                                <td><a href="{{ route('lecturers.show', $lecturer) }}" class="strong">{{ $lecturer->name }}</a></td>
                                <td>{{ $lecturer->email }}</td>
                                <td>{{ $lecturer->phone ?? '–' }}</td>
                                <td>{{ $lecturer->lecturedSubjects->pluck('code')->implode(', ') ?: 'None yet' }}</td>
                                <td class="num">{{ $lecturer->term_sessions_count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection

@extends('layouts.app')
@section('title', 'Teachers')
@section('subtitle', 'Teacher accounts are added by the super admin. The head of department assigns them to subjects.')

@section('content')
    <section class="panel flush">
        @if ($teachers->isEmpty())
            <div class="empty-state"><p>No teachers yet.</p></div>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>Staff no.</th><th>Name</th><th>Email</th><th>Phone</th><th>Subjects</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($teachers as $teacher)
                            <tr>
                                <td class="ref">{{ $teacher->staff_no ?? '–' }}</td>
                                <td><a href="{{ route('teachers.show', $teacher) }}" class="strong">{{ $teacher->name }}</a></td>
                                <td>{{ $teacher->email }}</td>
                                <td>{{ $teacher->phone ?? '–' }}</td>
                                <td>{{ $teacher->subjects->pluck('code')->implode(', ') ?: 'None yet' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection

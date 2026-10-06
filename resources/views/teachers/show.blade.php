@extends('layouts.app')
@section('title', $teacher->name)
@section('back')<a href="{{ route('teachers.index') }}">Teachers</a>@endsection

@section('content')
    <section class="panel">
        <dl class="details">
            <div><dt>Staff number</dt><dd>{{ $teacher->staff_no ?? 'Not recorded' }}</dd></div>
            <div><dt>Email</dt><dd><a href="mailto:{{ $teacher->email }}">{{ $teacher->email }}</a></dd></div>
            <div><dt>Phone</dt><dd>{{ $teacher->phone ?? 'Not recorded' }}</dd></div>
            <div><dt>Account added</dt><dd>{{ $teacher->created_at?->format('j F Y') }}</dd></div>
        </dl>
    </section>

    <section class="panel flush">
        <div class="panel-header inset"><h2>Subjects taught</h2></div>
        @if ($teacher->subjects->isEmpty())
            <div class="empty-state"><p>Not assigned to any subjects yet.</p></div>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Code</th><th>Subject</th><th class="num">Credit hours</th><th class="num">Students</th></tr></thead>
                    <tbody>
                        @foreach ($teacher->subjects as $subject)
                            <tr>
                                <td class="ref strong">{{ $subject->code }}</td>
                                <td>{{ $subject->name }}</td>
                                <td class="num">{{ $subject->credit_hours }}</td>
                                <td class="num">{{ $subject->results_count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection

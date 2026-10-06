@extends('layouts.app')
@section('title', 'Terms')
@section('subtitle', 'Academic terms. Sessions and timetables belong to a term; registration always uses the current term.')

@section('actions')
    <a href="{{ route('terms.create') }}" class="btn btn-primary">Add term</a>
@endsection

@section('content')
    <section class="panel flush">
        @if ($terms->isEmpty())
            <div class="empty-state">
                <p>No terms yet. Add the current term so heads of department can build the timetable.</p>
                <a href="{{ route('terms.create') }}" class="btn btn-primary">Add the first term</a>
            </div>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>Term</th><th>Dates</th><th class="num">Sessions</th><th>Status</th><th><span class="visually-hidden">Actions</span></th></tr>
                    </thead>
                    <tbody>
                        @foreach ($terms as $term)
                            <tr>
                                <td class="strong">{{ $term->name }}</td>
                                <td>
                                    @if ($term->starts_on)
                                        {{ $term->starts_on->format('j M Y') }} to {{ $term->ends_on?->format('j M Y') ?? '?' }}
                                    @else
                                        <span class="muted">Not set</span>
                                    @endif
                                </td>
                                <td class="num">{{ $term->sessions_count }}</td>
                                <td>
                                    @if ($term->is_current)
                                        <span class="status status-active">Current term</span>
                                    @else
                                        <form method="POST" action="{{ route('terms.current', $term) }}">
                                            @csrf
                                            <button type="submit" class="link-button">Make current</button>
                                        </form>
                                    @endif
                                </td>
                                <td class="row-actions">
                                    <a href="{{ route('sessions.index', ['term' => $term->id]) }}">Sessions</a>
                                    <a href="{{ route('terms.edit', $term) }}">Edit</a>
                                    @unless ($term->is_current || $term->sessions_count)
                                        <form method="POST" action="{{ route('terms.destroy', $term) }}"
                                              onsubmit="return confirm(@js('Delete term '.$term->name.'?'))">
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
        @endif
    </section>
@endsection

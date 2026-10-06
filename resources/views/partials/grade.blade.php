{{-- Grade badge, coloured by band (A, B, C, D/F). Usage: @include('partials.grade', ['grade' => $result->grade]) --}}
@php
    $band = match (substr($grade, 0, 1)) {
        'A' => 'a', 'B' => 'b', 'C' => 'c', default => 'fail',
    };
@endphp
<span class="grade grade-{{ $band }}">{{ $grade }}</span>

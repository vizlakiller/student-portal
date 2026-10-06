{{-- The grading scale from config/grading.php, shown beside the result forms. --}}
<section class="panel narrow">
    <h2>Grading scale</h2>
    <table class="table compact scale">
        <thead>
            <tr><th>Marks</th><th>Grade</th><th class="num">Point</th></tr>
        </thead>
        <tbody>
            @php $upper = 100; @endphp
            @foreach (config('grading.scale') as $min => [$grade, $point])
                <tr>
                    <td class="num-left">{{ $min }} to {{ $upper }}</td>
                    <td>@include('partials.grade', ['grade' => $grade])</td>
                    <td class="num">{{ number_format($point, 2) }}</td>
                </tr>
                @php $upper = $min - 1; @endphp
            @endforeach
        </tbody>
    </table>
</section>

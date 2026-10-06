<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => e('Academic transcript: '.$student->name)])
</head>
<body class="print-page">
    <div class="print-toolbar">
        <a href="{{ auth()->user()->hasRole('student') ? route('my.results') : route('students.show', $student) }}" class="btn">Back</a>
        <button type="button" class="btn btn-primary" onclick="window.print()">Print or save as PDF</button>
    </div>

    <article class="transcript">
        <header class="transcript-header">
            <div class="print-brand">
                @if (\App\Support\Branding::logoUrl())
                    <img src="{{ \App\Support\Branding::logoUrl() }}" alt="" class="print-logo">
                @endif
                <div>
                    <p class="transcript-institution">{{ \App\Support\Branding::institution() }}</p>
                    <h1>Academic transcript</h1>
                </div>
            </div>
            <p class="muted">Issued {{ now()->format('j F Y') }}</p>
        </header>

        <dl class="transcript-details">
            <div><dt>Name</dt><dd>{{ $student->name }}</dd></div>
            <div><dt>Student number</dt><dd>{{ $student->student_no }}</dd></div>
            <div><dt>Programme</dt><dd>{{ $student->programme->name }}</dd></div>
            <div><dt>Intake year</dt><dd>{{ $student->intake_year }}</dd></div>
            <div><dt>Status</dt><dd>{{ $student->status }}</dd></div>
        </dl>

        @forelse ($semesters->filter(fn ($data) => $data['gpa'] !== null) as $semester => $data)
            <section class="transcript-semester">
                <h2>Semester {{ $semester }}</h2>
                <table class="table results-table">
                    <thead>
                        <tr>
                            <th>Code</th><th>Subject</th>
                            <th class="num">Credits</th><th>Grade</th><th class="num">Point</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data['results']->filter->isMarked() as $result)
                            <tr>
                                <td>{{ $result->subject->code }}</td>
                                <td>{{ $result->subject->name }}</td>
                                <td class="num">{{ $result->subject->credit_hours }}</td>
                                <td>{{ $result->grade }}</td>
                                <td class="num">{{ number_format($result->grade_point, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2">Semester GPA</td>
                            <td class="num">{{ $data['credits'] }}</td>
                            <td></td>
                            <td class="num">{{ number_format($data['gpa'], 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </section>
        @empty
            <p>No results recorded.</p>
        @endforelse

        <footer class="transcript-summary">
            <div><span>Total credit hours</span><strong>{{ $credits }}</strong></div>
            <div><span>Cumulative GPA (CGPA)</span><strong>{{ $cgpa !== null ? number_format($cgpa, 2) : '–' }}</strong></div>
        </footer>

        <p class="transcript-note">
            Grade points: {{ collect(config('grading.scale'))->map(fn ($row) => $row[0].' '.number_format($row[1], 2))->implode(', ') }}.
            This is a computer-generated document.
        </p>
    </article>
</body>
</html>

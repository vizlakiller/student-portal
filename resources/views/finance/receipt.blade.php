@use('App\Support\Branding')
<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => e('Receipt '.$payment->receipt_no)])
</head>
<body class="print-page">
    <div class="print-toolbar">
        <a href="{{ route('finance.students.show', $payment->student) }}" class="btn">Back to statement</a>
        <button type="button" class="btn btn-primary" onclick="window.print()">Print or save as PDF</button>
    </div>

    @if (session('success'))
        <div class="alert alert-success print-alert no-print" role="status">{{ session('success') }}</div>
    @endif

    <article class="transcript receipt">
        <header class="transcript-header">
            <div class="print-brand">
                @if (Branding::logoUrl())
                    <img src="{{ Branding::logoUrl() }}" alt="" class="print-logo">
                @endif
                <div>
                    <p class="transcript-institution">{{ Branding::institution() }}</p>
                    <h1>Official receipt</h1>
                </div>
            </div>
            <div class="receipt-no">
                <span class="muted">Receipt no.</span>
                <strong>{{ $payment->receipt_no }}</strong>
            </div>
        </header>

        <dl class="transcript-details">
            <div><dt>Received from</dt><dd>{{ $payment->student->name }}</dd></div>
            <div><dt>Student number</dt><dd>{{ $payment->student->student_no }}</dd></div>
            <div><dt>Programme</dt><dd>{{ $payment->student->programme->name }}</dd></div>
            <div><dt>Payment date</dt><dd>{{ $payment->paid_at->format('j F Y') }}</dd></div>
            <div><dt>Method</dt><dd>{{ $payment->method }}</dd></div>
            <div><dt>Reference</dt><dd>{{ $payment->reference ?: '–' }}</dd></div>
        </dl>

        <div class="receipt-amount">
            <span>Amount received</span>
            <strong>{{ Branding::money($payment->amount) }}</strong>
        </div>

        @if ($payment->notes)
            <p><span class="muted">Notes:</span> {{ $payment->notes }}</p>
        @endif

        <footer class="transcript-summary">
            <div>
                <span>Balance after this receipt (as of {{ now()->format('j M Y') }})</span>
                <strong>
                    @if ($balance > 0)
                        {{ Branding::money($balance) }} owing
                    @elseif ($balance < 0)
                        {{ Branding::money(abs($balance)) }} credit
                    @else
                        Fully paid
                    @endif
                </strong>
            </div>
        </footer>

        <p class="transcript-note">
            Received by {{ $payment->receiver?->name ?? 'staff' }}. This is a computer-generated receipt and needs no signature.
        </p>
    </article>
</body>
</html>

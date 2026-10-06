@extends('layouts.app')
@section('title', 'Fees and payments')
@section('subtitle', 'Balances are worked out from charges billed minus payments received.')

@section('actions')
    @can('manage-finance')
        <a href="{{ route('finance.billing.create') }}" class="btn btn-primary">Bill a programme</a>
    @endcan
@endsection

@use('App\Support\Branding')

@section('content')
    <section class="stat-strip" aria-label="Summary">
        <div class="stat">
            <span class="stat-value money">{{ Branding::money($totals['outstanding']) }}</span>
            <span class="stat-label">Owed by {{ $totals['owing'] }} {{ str('student')->plural($totals['owing']) }}</span>
        </div>
        <div class="stat">
            <span class="stat-value money">{{ Branding::money($totals['this_month']) }}</span>
            <span class="stat-label">Collected in {{ now()->format('F') }}</span>
        </div>
        <div class="stat">
            <span class="stat-value money">{{ Branding::money($totals['collected']) }}</span>
            <span class="stat-label">Collected in total</span>
        </div>
        <div class="stat">
            <span class="stat-value money">{{ Branding::money($totals['billed']) }}</span>
            <span class="stat-label">Billed in total</span>
        </div>
    </section>

    <form method="GET" action="{{ route('finance.index') }}" class="filter-bar" role="search">
        <div class="field grow">
            <label for="search">Find a student</label>
            <input id="search" type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name, student number or email">
        </div>
        <div class="field">
            <label for="programme">Programme</label>
            <select id="programme" name="programme">
                <option value="">All programmes</option>
                @foreach ($programmes as $programme)
                    <option value="{{ $programme->id }}" @selected(($filters['programme'] ?? '') == $programme->id)>{{ $programme->code }}</option>
                @endforeach
            </select>
        </div>
        <label class="checkbox filter-check">
            <input type="checkbox" name="owing" value="1" @checked($filters['owing'] ?? false)> Only students who owe money
        </label>
        <div class="filter-buttons">
            <button type="submit" class="btn btn-primary">Apply</button>
            @if (array_filter($filters))
                <a href="{{ route('finance.index') }}" class="btn btn-quiet">Clear</a>
            @endif
        </div>
    </form>

    <div class="finance-layout">
        <section class="panel flush">
            @if ($students->isEmpty())
                <div class="empty-state"><p>No students match.</p></div>
            @else
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Programme</th>
                                <th class="num">Billed</th>
                                <th class="num">Paid</th>
                                <th class="num">Balance</th>
                                <th><span class="visually-hidden">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($students as $student)
                                @php $balance = $student->balance(); @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route('finance.students.show', $student) }}" class="strong">{{ $student->name }}</a>
                                        <div class="muted small">{{ $student->student_no }}</div>
                                    </td>
                                    <td>{{ $student->programme->code }}</td>
                                    <td class="num">{{ number_format((float) $student->charges_sum_amount, 2) }}</td>
                                    <td class="num">{{ number_format((float) $student->payments_sum_amount, 2) }}</td>
                                    <td class="num">@include('finance._balance', ['balance' => $balance])</td>
                                    <td class="row-actions">
                                        @can('manage-finance')
                                            <a href="{{ route('finance.payments.create', $student) }}">Record payment</a>
                                        @else
                                            <a href="{{ route('finance.students.show', $student) }}">Statement</a>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="panel">
            <h2>Recent payments</h2>
            @if ($recentPayments->isEmpty())
                <p class="empty">No payments yet.</p>
            @else
                <ul class="recent-list">
                    @foreach ($recentPayments as $payment)
                        <li>
                            <a href="{{ route('finance.payments.receipt', $payment) }}">
                                <span>
                                    <strong>{{ $payment->student->name }}</strong>
                                    <span class="muted small">{{ $payment->receipt_no }}, {{ $payment->paid_at->format('j M') }}</span>
                                </span>
                                <span class="num strong">{{ number_format($payment->amount, 2) }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    {{ $students->links() }}
@endsection

@extends('layouts.app')
@section('title', 'Fees: '.$student->name)
@section('subtitle', $student->student_no.', '.$student->programme->code.', semester '.$student->semester)
@section('back')<a href="{{ route('finance.index') }}">Fees and payments</a>@endsection

@use('App\Support\Branding')

@section('actions')
    <button type="button" class="btn" onclick="window.print()">Print statement</button>
    @can('manage-finance')
        <a href="{{ route('finance.charges.create', $student) }}" class="btn">Add charge</a>
        <a href="{{ route('finance.payments.create', $student) }}" class="btn btn-primary">Record payment</a>
    @endcan
@endsection

@section('content')
    <section class="stat-strip three" aria-label="Summary">
        <div class="stat">
            <span class="stat-value money">{{ Branding::money($charged) }}</span>
            <span class="stat-label">Billed</span>
        </div>
        <div class="stat">
            <span class="stat-value money">{{ Branding::money($paid) }}</span>
            <span class="stat-label">Paid</span>
        </div>
        <div class="stat">
            <span class="stat-value money">{{ Branding::money(abs($balance)) }}</span>
            <span class="stat-label">{{ $balance > 0 ? 'Still owing' : ($balance < 0 ? 'In credit (paid in advance)' : 'Fully paid') }}</span>
        </div>
    </section>

    <section class="panel flush">
        <div class="panel-header inset"><h2>Statement</h2></div>
        @if ($entries->isEmpty())
            <div class="empty-state">
                <p>No charges or payments yet.</p>
                @can('manage-finance')
                    <a href="{{ route('finance.charges.create', $student) }}" class="btn">Add the first charge</a>
                @endcan
            </div>
        @else
            <div class="table-wrap">
                <table class="table statement">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th class="num">Charge</th>
                            <th class="num">Payment</th>
                            <th class="num">Balance</th>
                            <th class="no-print"><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($entries as $entry)
                            <tr>
                                <td class="num-left">{{ $entry['date']->format('j M Y') }}</td>
                                <td>{{ $entry['description'] }}</td>
                                <td class="num">{{ $entry['charge'] !== null ? number_format($entry['charge'], 2) : '' }}</td>
                                <td class="num">{{ $entry['payment'] !== null ? number_format($entry['payment'], 2) : '' }}</td>
                                <td class="num">@include('finance._balance', ['balance' => $entry['balance']])</td>
                                <td class="row-actions no-print">
                                    @if ($entry['payment'] !== null)
                                        <a href="{{ route('finance.payments.receipt', $entry['model']) }}">Receipt</a>
                                        @can('delete-payments')
                                            <form method="POST" action="{{ route('finance.payments.destroy', $entry['model']) }}"
                                                  onsubmit="return confirm(@js('Delete payment '.$entry['model']->receipt_no.'? Its receipt will no longer be valid.'))">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="link-button danger">Delete</button>
                                            </form>
                                        @endcan
                                    @elsecan('manage-finance')
                                        <form method="POST" action="{{ route('finance.charges.destroy', $entry['model']) }}"
                                              onsubmit="return confirm(@js('Remove the charge "'.$entry['model']->description.'"?'))">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="link-button danger">Remove</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection

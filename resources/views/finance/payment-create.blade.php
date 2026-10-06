@extends('layouts.app')
@section('title', 'Record payment')
@section('subtitle', $student->name.' ('.$student->student_no.')')
@section('back')<a href="{{ route('finance.students.show', $student) }}">Back to statement</a>@endsection

@section('content')
    <form method="POST" action="{{ route('finance.payments.store', $student) }}" class="panel form narrow">
        @csrf

        <p>
            Current balance:
            @include('finance._balance', ['balance' => $balance])
        </p>

        <div class="form-grid">
            <x-input label="Amount ({{ \App\Support\Branding::currency() }})" name="amount" type="number" step="0.01" min="0.01"
                     :value="$balance > 0 ? number_format($balance, 2, '.', '') : null" required />
            <x-input label="Payment date" name="paid_at" type="date" :value="now()->format('Y-m-d')" required
                     max="{{ now()->format('Y-m-d') }}" />
            <x-select label="Method" name="method" required
                      :options="array_combine(\App\Models\Payment::METHODS, \App\Models\Payment::METHODS)" value="Cash" />
            <x-input label="Reference" name="reference" placeholder="Bank reference or cheque no." />
        </div>
        <x-textarea label="Notes" name="notes" rows="2" />

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save and print receipt</button>
            <a href="{{ route('finance.students.show', $student) }}" class="btn btn-quiet">Cancel</a>
        </div>
    </form>
@endsection

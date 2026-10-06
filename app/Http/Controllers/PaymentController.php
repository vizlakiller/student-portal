<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Accountant: record money received and print the receipt.
 */
class PaymentController extends Controller
{
    public function create(Student $student)
    {
        return view('finance.payment-create', [
            'student' => $student,
            'balance' => $student->balance(),
        ]);
    }

    public function store(Request $request, Student $student)
    {
        $data = $request->validate([
            'amount'    => ['required', 'decimal:0,2', 'min:0.01', 'max:1000000'],
            'method'    => ['required', Rule::in(Payment::METHODS)],
            'reference' => ['nullable', 'string', 'max:100'],
            'paid_at'   => ['required', 'date', 'before_or_equal:today'],
            'notes'     => ['nullable', 'string', 'max:500'],
        ], [
            'paid_at.before_or_equal' => 'The payment date cannot be in the future.',
        ], [
            'paid_at' => 'payment date',
        ]);

        // Inside a transaction so two payments can't get the same receipt number.
        $payment = DB::transaction(fn () => $student->payments()->create([
            ...$data,
            'receipt_no'  => Payment::nextReceiptNo(),
            'received_by' => $request->user()->id,
        ]));

        return redirect()
            ->route('finance.payments.receipt', $payment)
            ->with('success', "Payment recorded. Receipt {$payment->receipt_no}.");
    }

    public function receipt(Payment $payment)
    {
        $payment->load(['student.programme', 'receiver']);

        return view('finance.receipt', [
            'payment' => $payment,
            'balance' => $payment->student->balance(),
        ]);
    }

    /**
     * Super admin only: remove a payment entered by mistake.
     */
    public function destroy(Payment $payment)
    {
        $student = $payment->student;
        $payment->delete();

        return redirect()
            ->route('finance.students.show', $student)
            ->with('success', "Payment {$payment->receipt_no} deleted.");
    }
}

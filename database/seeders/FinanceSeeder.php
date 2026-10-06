<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Sample tuition fees and payments. Most students have paid; some still owe.
 */
class FinanceSeeder extends Seeder
{
    /** Tuition fee per semester for each programme (RM). */
    private const FEES = ['DCS' => 2450, 'DIT' => 2300, 'DEE' => 2600, 'BCS' => 3800, 'CBM' => 1500];

    public function run(): void
    {
        mt_srand(77);

        $accountantId = User::where('email', 'accountant@example.com')->value('id');
        $payments = [];   // created at the end, in date order, so receipt numbers follow the dates

        $students = Student::with('programme')->orderBy('id')->get();

        foreach ($students as $student) {
            if ($student->charges()->exists()) {
                continue;   // already seeded
            }

            $fee = self::FEES[$student->programme->code] ?? 2000;
            $lastSemester = min($student->semester, 4);

            for ($sem = 1; $sem <= $lastSemester; $sem++) {
                // Semesters start in September and March
                $billedOn = Carbon::create($student->intake_year, 9, 1)->addMonths(6 * ($sem - 1));
                if ($billedOn->isFuture()) {
                    $billedOn = now()->subDays(20);
                }

                $charge = $student->charges()->create([
                    'description' => "Semester {$sem} tuition fee",
                    'amount'      => $fee,
                    'semester'    => $sem,
                    'due_date'    => $billedOn->copy()->addDays(30),
                    'created_by'  => $accountantId,
                ]);
                $charge->forceFill(['created_at' => $billedOn, 'updated_at' => $billedOn])->save();

                // Older semesters are paid in full; the latest one is often part-paid or unpaid.
                $isLatest = $sem === $lastSemester;
                $roll = mt_rand(1, 100);
                $amount = match (true) {
                    ! $isLatest                         => $fee,
                    $student->status === 'Withdrawn'    => 0,
                    $roll <= 55                         => $fee,
                    $roll <= 80                         => round($fee * 0.5, 2),
                    default                             => 0,
                };

                // Some students pay the latest semester in two instalments, a month apart.
                $instalments = match (true) {
                    $amount <= 0                              => [],
                    $isLatest && $amount == $fee && mt_rand(0, 1) => [[$fee / 2, mt_rand(3, 14)], [$fee / 2, mt_rand(30, 40)]],
                    default                                   => [[$amount, mt_rand(3, 28)]],
                };

                foreach ($instalments as [$instalment, $afterDays]) {
                    $paidAt = $billedOn->copy()->addDays($afterDays);
                    if ($paidAt->isFuture()) {
                        $paidAt = now();
                    }

                    $payments[] = [$student, [
                        'amount'      => round($instalment, 2),
                        'method'      => Payment::METHODS[mt_rand(0, 3)],
                        'reference'   => mt_rand(0, 1) ? 'TRX'.mt_rand(100000, 999999) : null,
                        'paid_at'     => $paidAt,
                        'received_by' => $accountantId,
                    ]];
                }
            }
        }

        usort($payments, fn ($a, $b) => $a[1]['paid_at'] <=> $b[1]['paid_at']);
        $counters = [];

        foreach ($payments as [$student, $payment]) {
            $year = $payment['paid_at']->year;
            $counters[$year] = ($counters[$year] ?? Payment::where('receipt_no', 'like', "RCP-{$year}-%")->count()) + 1;

            $student->payments()->create([...$payment, 'receipt_no' => sprintf('RCP-%d-%05d', $year, $counters[$year])]);
        }
    }
}

<?php

namespace Tests\Feature;

use App\Models\Charge;
use App\Models\Payment;
use App\Models\Programme;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_accountant_records_payments_with_numbered_receipts(): void
    {
        $this->actingAsRole('accountant');
        $student = Student::factory()->create();
        $student->charges()->create(['description' => 'Semester 1 tuition fee', 'amount' => 2500]);

        $this->post("/finance/students/{$student->id}/payments", [
            'amount' => '1000.50', 'method' => 'Cash', 'paid_at' => now()->format('Y-m-d'),
        ])->assertRedirect();

        $first = Payment::firstOrFail();
        $this->assertSame('RCP-'.now()->year.'-00001', $first->receipt_no);

        $this->post("/finance/students/{$student->id}/payments", [
            'amount' => '500', 'method' => 'Online transfer', 'reference' => 'TRX123', 'paid_at' => now()->format('Y-m-d'),
        ]);
        $this->assertSame('RCP-'.now()->year.'-00002', Payment::latest('id')->first()->receipt_no);

        $this->assertSame(999.5, $student->fresh()->balance());   // 2500 - 1000.50 - 500

        $this->get("/finance/payments/{$first->id}/receipt")->assertOk()
            ->assertSee('Official receipt')->assertSee('RM 1,000.50')->assertSee('RM 999.50 owing');
    }

    public function test_payment_must_be_positive_and_not_in_the_future(): void
    {
        $this->actingAsRole('accountant');
        $student = Student::factory()->create();

        $this->post("/finance/students/{$student->id}/payments", ['amount' => 0, 'method' => 'Cash', 'paid_at' => now()->format('Y-m-d')])
            ->assertSessionHasErrors('amount');
        $this->post("/finance/students/{$student->id}/payments", ['amount' => 10, 'method' => 'Cash', 'paid_at' => now()->addDay()->format('Y-m-d')])
            ->assertSessionHasErrors('paid_at');
        $this->post("/finance/students/{$student->id}/payments", ['amount' => 10, 'method' => 'Bitcoin', 'paid_at' => now()->format('Y-m-d')])
            ->assertSessionHasErrors('method');
    }

    public function test_billing_a_programme_charges_active_students_once(): void
    {
        $this->actingAsRole('accountant');
        $dcs = Programme::factory()->create();
        $active = Student::factory()->count(3)->create(['programme_id' => $dcs->id, 'status' => 'Active']);
        Student::factory()->create(['programme_id' => $dcs->id, 'status' => 'Withdrawn']);
        Student::factory()->create(['status' => 'Active']);   // another programme

        $bill = ['programme_id' => $dcs->id, 'description' => 'Semester 2 tuition fee', 'amount' => '2450'];

        $this->post('/finance/billing', $bill)->assertSessionHas('success');
        $this->assertSame(3, Charge::count());

        $this->post('/finance/billing', $bill)->assertSessionHas('error');   // all already billed
        $this->assertSame(3, Charge::count());
        $this->assertSame(2450.0, $active->first()->fresh()->balance());
    }

    public function test_accountant_adds_and_removes_charges_and_sees_debtors(): void
    {
        $this->actingAsRole('accountant');
        $owing = Student::factory()->create(['name' => 'Owing Student']);
        Student::factory()->create(['name' => 'Clear Student']);

        $this->post("/finance/students/{$owing->id}/charges", ['description' => 'Hostel fee', 'amount' => '600'])
            ->assertRedirect(route('finance.students.show', $owing));

        $this->get('/finance?owing=1')->assertSee('Owing Student')->assertDontSee('Clear Student')->assertSee('RM 600.00');
        $this->get("/finance/students/{$owing->id}")->assertOk()->assertSee('Hostel fee');

        $this->delete('/finance/charges/'.Charge::first()->id)->assertRedirect();
        $this->assertSame(0.0, $owing->fresh()->balance());
    }

    public function test_only_super_admin_can_delete_a_payment(): void
    {
        $student = Student::factory()->create();
        $payment = $student->payments()->create(['receipt_no' => 'RCP-2026-00009', 'amount' => 100, 'method' => 'Cash', 'paid_at' => now()]);

        $this->actingAsRole('accountant');
        $this->delete("/finance/payments/{$payment->id}")->assertForbidden();

        $this->actingAsRole('super_admin');
        $this->delete("/finance/payments/{$payment->id}")->assertRedirect();
        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
    }
}

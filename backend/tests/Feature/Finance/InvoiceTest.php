<?php

namespace Tests\Feature\Finance;

use App\Enums\Role;
use App\Models\Invoice;
use App\Models\Lecturer;
use App\Models\Payment;
use App\Models\Role as RoleModel;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Module 9.18 — invoices from items, numbering, status derivation, payments
 * within the balance, append-only reversals, cancellation, access.
 */
class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-03-10 09:00:00');

        $this->admin = User::factory()->superAdmin()->create();
        $this->student = Student::factory()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_create_computes_totals_and_numbers_invoices(): void
    {
        $data = $this->actingAs($this->admin)->postJson('/api/invoices', $this->payload())->assertCreated()->json('data');

        // 600 + 2 × 12.50 = 625 − 25 discount = 600.
        $this->assertSame('INV-2026-00001', $data['invoice_number']);
        $this->assertEquals(625, $data['subtotal']);
        $this->assertEquals(600, $data['total']);
        $this->assertEquals(600, $data['balance']);
        $this->assertSame('pending', $data['status']);
        $this->assertCount(2, $data['items']);
        $this->assertEquals(25, $data['items'][1]['amount']);

        $this->actingAs($this->admin)->postJson('/api/invoices', $this->payload())->assertCreated()->assertJsonPath('data.invoice_number', 'INV-2026-00002');
    }

    public function test_invoice_validation(): void
    {
        $as = $this->actingAs($this->admin);

        $as->postJson('/api/invoices', $this->payload(['items' => []]))->assertJsonValidationErrors('items');
        $as->postJson('/api/invoices', $this->payload(['discount' => 700]))->assertJsonValidationErrors('discount');
        $as->postJson('/api/invoices', $this->payload(['due_date' => '2026-03-01']))->assertJsonValidationErrors('due_date');
        $as->postJson('/api/invoices', $this->payload(['currency' => 'EUR']))->assertJsonValidationErrors('currency');
        // 1.5 × 0.33 = 0.495 is not a whole cent.
        $as->postJson('/api/invoices', $this->payload(['items' => [['description' => 'Odd', 'quantity' => 1.5, 'unit_price' => 0.33]]]))->assertJsonValidationErrors('items.0.quantity');
        $as->postJson('/api/invoices', $this->payload(['items' => [['description' => 'Zero qty', 'quantity' => 0, 'unit_price' => 5]]]))->assertJsonValidationErrors('items.0.quantity');
    }

    public function test_payments_drive_status_and_reject_overpayment(): void
    {
        $id = $this->createInvoice();
        $url = "/api/invoices/{$id}/payments";
        $as = $this->actingAs($this->admin);

        $as->postJson($url, $this->payment(['amount' => 600.01]))->assertJsonValidationErrors('amount');
        $as->postJson($url, $this->payment(['paid_on' => '2026-03-11']))->assertJsonValidationErrors('paid_on');
        $as->postJson($url, $this->payment(['method' => 'card']))->assertJsonValidationErrors('method');

        $as->postJson($url, $this->payment(['amount' => 250]))->assertCreated()->assertJsonPath('data.status', 'partial')->assertJsonPath('data.balance', 350);
        $as->postJson($url, $this->payment(['amount' => 350]))->assertCreated()->assertJsonPath('data.status', 'paid')->assertJsonPath('data.balance', 0);
        $as->postJson($url, $this->payment(['amount' => 1]))->assertJsonValidationErrors('amount');

        // Paid invoices cannot be edited or cancelled.
        $edit = $this->payload();
        unset($edit['student_id']);
        $as->putJson("/api/invoices/{$id}", $edit)->assertStatus(409);
        $as->postJson("/api/invoices/{$id}/cancel")->assertStatus(409);
    }

    public function test_reversal_is_append_only(): void
    {
        $id = $this->createInvoice();
        $this->actingAs($this->admin)->postJson("/api/invoices/{$id}/payments", $this->payment(['amount' => 600]));
        $payment = Payment::query()->firstOrFail();

        $this->actingAs($this->admin)->postJson("/api/payments/{$payment->id}/reverse")->assertJsonValidationErrors('reason');
        $data = $this->actingAs($this->admin)->postJson("/api/payments/{$payment->id}/reverse", ['reason' => 'Bounced transfer'])
            ->assertOk()->assertJsonPath('data.status', 'pending')->assertJsonPath('data.amount_paid', 0)->json('data');

        $this->assertCount(2, $data['payments']);
        $this->assertTrue($data['payments'][0]['reversed']);
        $this->assertTrue($data['payments'][1]['is_reversal']);
        $this->assertSame($payment->id, $data['payments'][1]['reversal_of']);
        $this->assertSame(2, Payment::query()->count()); // nothing deleted

        $this->actingAs($this->admin)->postJson("/api/payments/{$payment->id}/reverse", ['reason' => 'Again'])->assertStatus(409);
        $this->actingAs($this->admin)->postJson('/api/payments/'.$data['payments'][1]['id'].'/reverse', ['reason' => 'x'])->assertStatus(409);

        // After a full reversal the invoice can be cancelled; then no payments.
        $this->actingAs($this->admin)->postJson("/api/invoices/{$id}/cancel", ['reason' => 'Scholarship'])->assertOk()->assertJsonPath('data.status', 'cancelled');
        $this->actingAs($this->admin)->postJson("/api/invoices/{$id}/payments", $this->payment())->assertStatus(409);
        $this->actingAs($this->admin)->postJson("/api/invoices/{$id}/cancel")->assertStatus(409);
    }

    public function test_overdue_is_derived_and_refreshed(): void
    {
        $id = $this->createInvoice(['due_date' => '2026-03-20']);
        $this->actingAs($this->admin)->postJson("/api/invoices/{$id}/payments", $this->payment(['amount' => 100]))->assertJsonPath('data.status', 'partial');

        Carbon::setTestNow('2026-03-25 09:00:00');
        $this->actingAs($this->admin)->getJson('/api/invoices?filters[status]=overdue')->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame('overdue', Invoice::query()->find($id)->status);

        // Settling an overdue invoice makes it paid.
        $this->actingAs($this->admin)->postJson("/api/invoices/{$id}/payments", $this->payment(['amount' => 500, 'paid_on' => '2026-03-25']))->assertJsonPath('data.status', 'paid');

        // The scheduled command does the same refresh.
        $other = $this->createInvoice(['issued_date' => '2026-03-01', 'due_date' => '2026-03-24']);
        Invoice::query()->whereKey($other)->update(['status' => 'pending']);
        $this->artisan('invoices:refresh-statuses')->expectsOutputToContain('1 invoice(s) marked overdue')->assertSuccessful();
    }

    public function test_edit_replaces_items_before_payments(): void
    {
        $id = $this->createInvoice();

        // An invoice never moves to another student.
        $this->actingAs($this->admin)->putJson("/api/invoices/{$id}", $this->payload(['student_id' => Student::factory()->create()->id]))
            ->assertJsonValidationErrors('student_id');
        $payload = $this->payload(['discount' => 0, 'items' => [['description' => 'Exam fee', 'quantity' => 1, 'unit_price' => 40, 'fee_category' => 'exam']]]);
        unset($payload['student_id']);
        $this->actingAs($this->admin)->putJson("/api/invoices/{$id}", $payload)->assertOk()->assertJsonPath('data.total', 40)->assertJsonCount(1, 'data.items');
    }

    public function test_access(): void
    {
        $id = $this->createInvoice();
        $faculty = User::factory()->create(['role_id' => RoleModel::factory()->withSlug(Role::FacultyAdmin->value)->create()->id]);
        $other = Student::factory()->create();

        $this->actingAs($this->student->user)->getJson("/api/invoices/{$id}")->assertOk();
        $this->actingAs($this->student->user)->getJson("/api/students/{$this->student->id}/invoices")->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('summary.0.currency', 'USD')->assertJsonPath('summary.0.balance', 600);
        $this->actingAs($this->student->user)->getJson('/api/invoices')->assertForbidden();
        $this->actingAs($this->student->user)->postJson("/api/invoices/{$id}/payments", $this->payment())->assertForbidden();
        $this->actingAs($other->user)->getJson("/api/invoices/{$id}")->assertForbidden();
        $this->actingAs($other->user)->getJson("/api/students/{$this->student->id}/invoices")->assertForbidden();
        $this->actingAs($faculty)->getJson('/api/invoices')->assertForbidden();
        $this->actingAs(Lecturer::factory()->create()->user)->getJson("/api/invoices/{$id}")->assertForbidden();
    }

    public function test_web_pages(): void
    {
        $this->actingAs($this->admin)->get('/invoices/create')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Invoices/Form')->where('invoice', null));
        $this->actingAs($this->admin)->post('/invoices', $this->payload())->assertRedirect();
        $invoice = Invoice::query()->firstOrFail();

        $this->actingAs($this->admin)->get('/invoices')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Invoices/Index')->has('invoices.data', 1));
        $this->actingAs($this->admin)->get("/invoices/{$invoice->id}")->assertOk()->assertInertia(fn (Assert $page) => $page->component('Invoices/Show')->where('invoice.total', 600));
        $this->actingAs($this->admin)->post("/invoices/{$invoice->id}/payments", $this->payment(['amount' => 100]))->assertSessionHas('success');
        $this->actingAs($this->admin)->get("/invoices/{$invoice->id}/edit")->assertOk();

        $this->actingAs($this->student->user)->get('/my-invoices')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Invoices/Mine')->has('data', 1)->where('summary.0.paid', 100));
        $this->actingAs($this->student->user)->get("/invoices/{$invoice->id}")->assertOk();
        $this->actingAs($this->student->user)->get('/invoices')->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'student_id' => $this->student->id,
            'title' => 'Tuition — Semester 2',
            'currency' => 'USD',
            'due_date' => '2026-04-10',
            'discount' => 25,
            'items' => [
                ['description' => 'Tuition', 'quantity' => 1, 'unit_price' => 600, 'fee_category' => 'tuition'],
                ['description' => 'Lab kit', 'quantity' => 2, 'unit_price' => 12.5, 'fee_category' => 'laboratory'],
            ],
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payment(array $overrides = []): array
    {
        return ['amount' => 100, 'paid_on' => '2026-03-10', 'method' => 'cash', 'reference' => 'R-1', ...$overrides];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createInvoice(array $overrides = []): int
    {
        return $this->actingAs($this->admin)->postJson('/api/invoices', $this->payload($overrides))->assertCreated()->json('data.id');
    }
}

<?php

use App\Enums\LedgerEntryType;
use App\Models\BillingPeriod;
use App\Models\Category;
use App\Models\Invoice;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\User;
use App\Services\ManualPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('staff and admin can manage categories, but regular user cannot', function () {
    $staff = User::factory()->staff()->create();
    $user = User::factory()->create();

    // Staff can view categories
    $this->actingAs($staff)->get(route('categories.index'))->assertOk();

    // Staff can create category
    $this->actingAs($staff)->post(route('categories.store'), [
        'name' => 'Study Tour Bali',
        'description' => 'Biaya bus dan penginapan',
    ])->assertRedirect()->assertSessionHas('status');

    expect(Category::where('name', 'Study Tour Bali')->exists())->toBeTrue();

    // Regular user is forbidden
    $this->actingAs($user)->get(route('categories.index'))->assertForbidden();
    $this->actingAs($user)->post(route('categories.store'), ['name' => 'Hacker Cat'])->assertForbidden();
});

test('payments inherit period category and ledger confirms payment with category in blockchain payload', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();

    $category = Category::create([
        'name' => 'Jaket Angkatan',
        'description' => 'Jaket kelas',
    ]);

    $period = BillingPeriod::factory()->create([
        'category_id' => $category->id,
        'title' => 'Jaket Angkatan 2026',
        'amount' => 120000,
        'qris_url' => 'https://example.test/qris.png',
    ]);

    $invoice = Invoice::factory()->create([
        'user_id' => $student->id,
        'billing_period_id' => $period->id,
        'amount' => 120000,
    ]);

    // Student visits payment page -> payment created with category
    $this->actingAs($student)->get(route('payments.create', $invoice))->assertOk()->assertSee('Jaket Angkatan');

    $payment = Payment::where('invoice_id', $invoice->id)->firstOrFail();
    expect($payment->category_id)->toBe($category->id);

    // Admin approves payment -> written to ledger with category in payload
    app(ManualPaymentService::class)->approve($payment, $admin);

    $entry = LedgerEntry::where('type', LedgerEntryType::PaymentConfirmed)
        ->where('ref_table', 'payments')
        ->where('ref_id', $payment->id)
        ->firstOrFail();

    $payload = json_decode($entry->payload, true);

    expect($payload['category'])->toBe('Jaket Angkatan')
        ->and($payload['amount'])->toBe(120000)
        ->and($payload['order_id'])->toBe($payment->order_id);

    // Ledger page displays the category
    $this->actingAs($student)
        ->get(route('ledger'))
        ->assertOk()
        ->assertSee('Kategori: Jaket Angkatan');
});

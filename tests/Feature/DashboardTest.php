<?php

use App\Enums\LedgerEntryType;
use App\Models\BillingPeriod;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PendingMember;
use App\Models\User;
use App\Services\Ledger;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('dashboard shows member payment and outstanding invoice statistics', function () {
    $user = User::factory()->create();
    $period = BillingPeriod::factory()->create(['title' => 'Kas Oktober']);
    $paidInvoice = Invoice::factory()->paid()->create(['user_id' => $user->id, 'billing_period_id' => $period->id, 'amount' => 10000]);
    $unpaidPeriod = BillingPeriod::factory()->create(['title' => 'Kas November']);
    $unpaidInvoice = Invoice::factory()->create(['user_id' => $user->id, 'billing_period_id' => $unpaidPeriod->id, 'amount' => 15000]);
    Payment::factory()->create(['invoice_id' => $paidInvoice->id, 'status' => 'paid', 'amount' => 10000]);
    Payment::factory()->create(['invoice_id' => $unpaidInvoice->id, 'status' => 'pending', 'amount' => 15000, 'proof_path' => 'payment-proofs/proof.png']);

    $this->actingAs($user)->get('/')
        ->assertOk()
        ->assertSee('Rp15.000')
        ->assertSee('Tagihan lunas')
        ->assertSee('Bukti menunggu review')
        ->assertSee('Kas Oktober')
        ->assertSee('Kas November');
});

test('staff dashboard shows payment review and member verification queue statistics', function () {
    $staff = User::factory()->staff()->create();
    $period = BillingPeriod::factory()->create();
    $invoice = Invoice::factory()->create(['billing_period_id' => $period->id]);
    Payment::factory()->create(['invoice_id' => $invoice->id, 'status' => 'pending', 'proof_path' => 'payment-proofs/proof.png']);
    PendingMember::factory()->create();

    $this->actingAs($staff)->get('/')
        ->assertOk()
        ->assertSee('Bukti menunggu review')
        ->assertSee('Menunggu verifikasi anggota')
        ->assertSee('1');
});

test('admin dashboard shows class collection and management statistics', function () {
    $admin = User::factory()->admin()->create();
    $period = BillingPeriod::factory()->create();
    Invoice::factory()->paid()->create(['billing_period_id' => $period->id, 'amount' => 12000]);
    Invoice::factory()->create(['billing_period_id' => $period->id, 'amount' => 12000]);
    PendingMember::factory()->create();
    app(Ledger::class)->append(LedgerEntryType::PaymentConfirmed, ['amount' => 12000]);

    $this->actingAs($admin)->get('/')
        ->assertOk()
        ->assertSee('Anggota aktif')
        ->assertSee('Periode tagihan')
        ->assertSee('Tingkat pelunasan')
        ->assertSee('50%')
        ->assertSee('Menunggu verifikasi anggota');
});

test('dashboard provides both view and verify ledger action buttons', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/')
        ->assertOk()
        ->assertSee('Lihat ledger')
        ->assertSee('Periksa ledger')
        ->assertSee(route('ledger'))
        ->assertSee(route('ledger.verify'));

    // Triggering verification from dashboard
    $this->actingAs($user)
        ->from('/')
        ->post(route('ledger.verify'))
        ->assertRedirect('/')
        ->assertSessionHas('status');
});

test('dashboard shows a monthly cash calendar with daily in and out', function () {
    $user = User::factory()->create();
    $ledger = app(Ledger::class);
    $ledger->append(LedgerEntryType::PaymentConfirmed, ['amount' => 50000]);
    $ledger->append(LedgerEntryType::ExpenseApproved, ['amount' => 15000]);

    $this->actingAs($user)->get('/')
        ->assertOk()
        ->assertSee('Kalender kas')
        ->assertSee('+50k')
        ->assertSee('-15k')
        ->assertViewHas('cashCalendar', fn ($calendar) => $calendar['totalIn'] === 50000
            && $calendar['totalOut'] === 15000
            && count($calendar['weeks']) >= 4
            && collect($calendar['weeks'])->every(fn ($week) => count($week) === 7));
});

test('cash calendar navigates by month and ignores an invalid month', function () {
    $user = User::factory()->create();
    app(Ledger::class)->append(LedgerEntryType::PaymentConfirmed, ['amount' => 50000]);

    $this->actingAs($user)->get('/?bulan=2020-02')
        ->assertOk()
        ->assertViewHas('cashCalendar', fn ($calendar) => $calendar['month']->format('Y-m') === '2020-02' && $calendar['totalIn'] === 0);

    $this->actingAs($user)->get('/?bulan=bukan-bulan')
        ->assertOk()
        ->assertViewHas('cashCalendar', fn ($calendar) => $calendar['month']->format('Y-m') === now()->format('Y-m'));
});

test('dashboard displays reversed status for reversed payments in history', function () {
    $user = User::factory()->create();
    $period = BillingPeriod::factory()->create();
    $invoice = Invoice::factory()->create(['user_id' => $user->id, 'billing_period_id' => $period->id]);

    Payment::create([
        'invoice_id' => $invoice->id,
        'order_id' => 'KAS-'.$invoice->id,
        'amount' => $invoice->amount,
        'status' => 'reversed',
        'proof_path' => 'payment-proofs/sample.jpg',
    ]);

    $this->actingAs($user)->get('/')
        ->assertOk()
        ->assertSee('Dibatalkan');
});

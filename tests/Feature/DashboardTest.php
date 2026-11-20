<?php

use App\Enums\LedgerEntryType;
use App\Livewire\CashCalendar;
use App\Models\BillingPeriod;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PendingMember;
use App\Models\User;
use App\Services\Ledger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

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

test('staff dashboard shows own unpaid invoice and quick payment button', function () {
    $staff = User::factory()->staff()->create(['name' => 'Bendahara 1']);
    $period = BillingPeriod::factory()->create(['title' => 'Kas Minggu 1']);
    $staffInvoice = Invoice::factory()->create([
        'user_id' => $staff->id,
        'billing_period_id' => $period->id,
        'amount' => 15000,
        'status' => 'unpaid',
    ]);

    $this->actingAs($staff)->get('/')
        ->assertOk()
        ->assertSee('Tagihan kas pribadimu')
        ->assertSee('Kas Minggu 1')
        ->assertSee('Bayar Kas')
        ->assertSee(route('payments.create', $staffInvoice));
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
        ->assertSeeLivewire(CashCalendar::class);

    Livewire::test(CashCalendar::class)
        ->assertViewHas('calendar', fn ($calendar) => $calendar['totalIn'] === 50000
            && $calendar['totalOut'] === 15000
            && count($calendar['weeks']) >= 4
            && collect($calendar['weeks'])->every(fn ($week) => count($week) === 7));
});

test('dashboard cash flow chart sums the last six months and nets reversals', function () {
    $user = User::factory()->create();
    $ledger = app(Ledger::class);
    $ledger->append(LedgerEntryType::PaymentConfirmed, ['amount' => 50000]);
    $ledger->append(LedgerEntryType::ExpenseApproved, ['amount' => 15000]);
    $ledger->append(LedgerEntryType::Reversal, ['type' => LedgerEntryType::ExpenseApproved->value, 'amount' => 5000]);

    $this->actingAs($user)->get('/')
        ->assertOk()
        ->assertSee('Arus kas')
        ->assertViewHas('cashFlow', fn ($flow) => count($flow) === 6
            && $flow[0]['month']->format('Y-m') === now()->subMonthsNoOverflow(5)->format('Y-m')
            && $flow[5]['in'] === 55000
            && $flow[5]['out'] === 15000
            && collect($flow)->take(5)->every(fn ($month) => $month['in'] === 0 && $month['out'] === 0));
});

test('cash calendar navigates by month without a page reload and ignores an invalid month', function () {
    app(Ledger::class)->append(LedgerEntryType::PaymentConfirmed, ['amount' => 50000]);

    Livewire::withQueryParams(['bulan' => '2020-02'])
        ->test(CashCalendar::class)
        ->assertViewHas('calendar', fn ($calendar) => $calendar['month']->format('Y-m') === '2020-02' && $calendar['totalIn'] === 0)
        ->call('nextMonth')
        ->assertSet('month', '2020-03')
        ->call('previousMonth')
        ->call('previousMonth')
        ->assertSet('month', '2020-01')
        ->call('thisMonth')
        ->assertViewHas('calendar', fn ($calendar) => $calendar['month']->format('Y-m') === now()->format('Y-m') && $calendar['totalIn'] === 50000);

    Livewire::withQueryParams(['bulan' => 'bukan-bulan'])
        ->test(CashCalendar::class)
        ->assertViewHas('calendar', fn ($calendar) => $calendar['month']->format('Y-m') === now()->format('Y-m'));
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

test('guests see the public cash summary without member data', function () {
    $user = User::factory()->create(['name' => 'Budi Rahasia']);
    $period = BillingPeriod::factory()->create(['title' => 'Kas Desember']);
    Invoice::factory()->create(['user_id' => $user->id, 'billing_period_id' => $period->id, 'amount' => 15000]);
    app(Ledger::class)->append(LedgerEntryType::PaymentConfirmed, ['amount' => 20000], $user);

    $admin = User::factory()->admin()->create();
    Expense::create(['title' => 'Tisu kelas', 'amount' => 8000, 'spent_at' => now(), 'notes' => 'Dibeli oleh Budi Rahasia', 'created_by' => $admin->id]);
    Payment::create(['invoice_id' => Invoice::first()->id, 'order_id' => 'KAS-X', 'amount' => 15000, 'status' => 'pending', 'proof_path' => 'payment-proofs/x.jpg']);
    PendingMember::factory()->create(['name' => 'Calon Rahasia']);

    $this->get('/')
        ->assertOk()
        ->assertSee('Rp20.000')
        ->assertSee('Utuh')
        ->assertSee(route('login'))
        ->assertSee('Per periode tagihan')
        ->assertSee('Kas Desember')
        ->assertSee('Arus kas')
        ->assertSee('Pengeluaran terbaru')
        ->assertSee('Tisu kelas')
        ->assertViewHas('cashFlow', fn ($flow) => count($flow) === 6)
        // Signed-in-only data never reaches guests.
        ->assertDontSee('Budi Rahasia')
        ->assertDontSee('Calon Rahasia')
        ->assertDontSee('Bukti terbaru')
        ->assertDontSee('Menunggu verifikasi anggota')
        ->assertDontSee(route('payments.review'));
});

test('reports detect an invoice whose confirmed payments cover it even if its cached status is stale', function () {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create(['name' => 'Siti Lunas']);
    $period = BillingPeriod::factory()->create();
    $invoice = Invoice::factory()->create(['user_id' => $member->id, 'billing_period_id' => $period->id, 'amount' => 10000, 'status' => 'unpaid']);
    Payment::factory()->create(['invoice_id' => $invoice->id, 'status' => 'paid', 'amount' => 10000]);

    $this->actingAs($admin)->get('/reports/payments?tab=matrix')->assertOk()->assertSee('Lunas');

    expect($invoice->fresh()->status)->toBe('paid');
});

test('sidebar shows pending review counts to staff but not to members', function () {
    Payment::factory()->count(3)->create(['status' => 'pending', 'proof_path' => 'payment-proofs/proof.png']);
    Payment::factory()->create(['status' => 'pending', 'proof_path' => null]);

    $this->actingAs(User::factory()->staff()->create())->get('/')
        ->assertOk()
        ->assertSeeHtml('3<span class="sr-only"> menunggu</span>');

    $this->actingAs(User::factory()->create())->get('/')
        ->assertOk()
        ->assertDontSeeHtml('<span class="sr-only"> menunggu</span>');
});

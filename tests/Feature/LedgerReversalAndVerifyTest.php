<?php

use App\Enums\LedgerEntryType;
use App\Models\BillingPeriod;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\User;
use App\Services\Ledger;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can trigger ledger audit and receive intact status', function () {
    $user = User::factory()->create();
    app(Ledger::class)->append(LedgerEntryType::PaymentConfirmed, ['amount' => 10000]);

    $this->actingAs($user)
        ->post(route('ledger.verify'))
        ->assertRedirect()
        ->assertSessionHas('status');
});

test('admin can reverse a confirmed payment, adjusting balance and reopening invoice', function () {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create();
    $period = BillingPeriod::factory()->create();
    $invoice = Invoice::factory()->paid()->create([
        'user_id' => $member->id,
        'billing_period_id' => $period->id,
        'amount' => 15000,
    ]);

    $payment = Payment::create([
        'invoice_id' => $invoice->id,
        'order_id' => 'KAS-'.$invoice->id,
        'amount' => 15000,
        'status' => 'paid',
    ]);

    $ledger = app(Ledger::class);
    $entry = $ledger->append(
        LedgerEntryType::PaymentConfirmed,
        ['order_id' => $payment->order_id, 'amount' => 15000],
        $admin,
        'payments',
        $payment->id,
    );

    expect($ledger->balance())->toBe(15000);

    // Admin reverses the entry
    $this->actingAs($admin)
        ->post(route('ledger.reverse', $entry), ['reason' => 'Salah konfirmasi nama siswa'])
        ->assertRedirect()
        ->assertSessionHas('status');

    $invoice->refresh();
    $payment->refresh();

    expect($invoice->status)->toBe('unpaid')
        ->and($payment->status)->toBe('reversed')
        ->and($ledger->balance())->toBe(0);

    // Ledger has REVERSAL entry
    $reversalEntry = LedgerEntry::where('type', LedgerEntryType::Reversal)->sole();
    expect($reversalEntry->payload)->toContain('"reversed_seq":'.$entry->seq);

    // Attempting to reverse again fails
    $this->actingAs($admin)
        ->post(route('ledger.reverse', $entry))
        ->assertRedirect()
        ->assertSessionHas('error');
});

test('admin can reverse an approved expense, adjusting balance and removing expense record without sequence prefix collision', function () {
    $admin = User::factory()->admin()->create();
    $ledger = app(Ledger::class);

    // Initial income
    $ledger->append(LedgerEntryType::PaymentConfirmed, ['amount' => 100000]);

    // Create an expense
    $expense = Expense::create([
        'title' => 'Buku Kas',
        'amount' => 20000,
        'spent_at' => now()->toDateString(),
        'created_by' => $admin->id,
    ]);

    $expenseEntry = $ledger->append(
        LedgerEntryType::ExpenseApproved,
        [
            'expense_id' => $expense->id,
            'title' => $expense->title,
            'amount' => $expense->amount,
        ],
        $admin,
        'expenses',
        $expense->id,
    );

    expect($ledger->balance())->toBe(80000);

    // Create mock reversal entry targeting sequence 10 to ensure prefix collision check doesn't block entry 1
    $ledger->append(
        LedgerEntryType::Reversal,
        [
            'reversed_seq' => 10,
            'type' => LedgerEntryType::ExpenseApproved->value,
            'amount' => 5000,
            'reason' => 'test prefix',
        ],
        $admin,
        'ledger_entries',
        10,
    );

    $this->actingAs($admin)
        ->post(route('ledger.reverse', $expenseEntry), ['reason' => 'Batal beli buku'])
        ->assertRedirect()
        ->assertSessionHas('status');

    expect($ledger->balance())->toBe(105000)
        ->and(Expense::find($expense->id))->toBeNull();
});

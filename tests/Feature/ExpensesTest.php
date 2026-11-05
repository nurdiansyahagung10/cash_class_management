<?php

use App\Enums\LedgerEntryType;
use App\Models\Expense;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Services\Ledger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('authenticated member can view expenses list', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();

    Expense::create([
        'title' => 'Spidol & Penghapus',
        'amount' => 25000,
        'spent_at' => now()->toDateString(),
        'created_by' => $admin->id,
    ]);

    $this->actingAs($user)
        ->get(route('expenses.index'))
        ->assertOk()
        ->assertSee('Spidol & Penghapus')
        ->assertSee('Rp25.000');
});

test('admin can record expense with receipt, writing EXPENSE_APPROVED to ledger and reducing balance', function () {
    Storage::fake();
    $admin = User::factory()->admin()->create();
    $ledger = app(Ledger::class);

    // Initial income 100.000
    $ledger->append(LedgerEntryType::PaymentConfirmed, ['amount' => 100000]);
    expect($ledger->balance())->toBe(100000);

    $receipt = UploadedFile::fake()->image('nota-spidol.jpg');

    $this->actingAs($admin)
        ->post(route('expenses.store'), [
            'title' => 'Beli Spidol Kelas',
            'amount' => 30000,
            'spent_at' => now()->toDateString(),
            'notes' => 'Toko Buku Gramedia',
            'receipt' => $receipt,
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    $expense = Expense::sole();
    expect($expense->title)->toBe('Beli Spidol Kelas')
        ->and($expense->amount)->toBe(30000)
        ->and($expense->notes)->toBe('Toko Buku Gramedia')
        ->and($expense->receipt_path)->not->toBeNull();

    Storage::assertExists($expense->receipt_path);

    // Verify ledger
    $entry = LedgerEntry::where('type', LedgerEntryType::ExpenseApproved)->sole();
    expect($entry->ref_table)->toBe('expenses')
        ->and($entry->ref_id)->toBe($expense->id)
        ->and($ledger->balance())->toBe(70000); // 100k - 30k = 70k

    // Receipt download works
    $this->actingAs($admin)
        ->get(route('expenses.receipt', $expense))
        ->assertOk();
});

test('regular user cannot record an expense', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('expenses.store'), [
            'title' => 'Spidol',
            'amount' => 10000,
            'spent_at' => now()->toDateString(),
        ])
        ->assertForbidden();

    expect(Expense::count())->toBe(0);
});

test('admin cannot record expense exceeding available ledger balance', function () {
    $admin = User::factory()->admin()->create();
    $ledger = app(Ledger::class);

    $ledger->append(LedgerEntryType::PaymentConfirmed, ['amount' => 50000]);
    expect($ledger->balance())->toBe(50000);

    $this->actingAs($admin)
        ->post(route('expenses.store'), [
            'title' => 'Beli Peralatan Mahal',
            'amount' => 60000,
            'spent_at' => now()->toDateString(),
        ])
        ->assertRedirect()
        ->assertSessionHasErrors(['amount']);

    expect(Expense::count())->toBe(0)
        ->and($ledger->balance())->toBe(50000);
});

test('user can search expenses by title or notes', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();

    Expense::create([
        'title' => 'Beli Spidol Whiteboard',
        'amount' => 15000,
        'spent_at' => now()->toDateString(),
        'created_by' => $admin->id,
    ]);

    Expense::create([
        'title' => 'Fotokopi Modul',
        'amount' => 10000,
        'spent_at' => now()->toDateString(),
        'created_by' => $admin->id,
    ]);

    $this->actingAs($user)->get(route('expenses.index', ['q' => 'Spidol']))
        ->assertOk()
        ->assertSee('Beli Spidol Whiteboard')
        ->assertDontSee('Fotokopi Modul');
});

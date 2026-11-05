<?php

use App\Enums\LedgerEntryType;
use App\Models\LedgerEntry;
use App\Services\Ledger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function seedLedger(int $count = 3): void
{
    foreach (range(1, $count) as $i) {
        app(Ledger::class)->append(LedgerEntryType::PaymentConfirmed, ['amount' => 10000, 'n' => $i]);
    }
}

test('entries are chained by hash', function () {
    seedLedger();

    $entries = LedgerEntry::orderBy('seq')->get();

    expect($entries[0]->prev_hash)->toBeNull()
        ->and($entries[1]->prev_hash)->toBe($entries[0]->hash)
        ->and($entries[2]->prev_hash)->toBe($entries[1]->hash)
        ->and(app(Ledger::class)->firstBrokenSeq())->toBeNull();
});

test('model refuses update and delete', function () {
    seedLedger(1);
    $entry = LedgerEntry::first();

    expect(fn () => $entry->update(['payload' => '{}']))->toThrow(LogicException::class)
        ->and(fn () => $entry->delete())->toThrow(LogicException::class);
});

test('database refuses update and delete', function () {
    seedLedger(1);

    expect(fn () => DB::table('ledger_entries')->update(['payload' => '{}']))->toThrow(Exception::class)
        ->and(fn () => DB::table('ledger_entries')->delete())->toThrow(Exception::class);
});

test('verify reports the first tampered entry', function () {
    seedLedger();

    DB::statement('DROP TRIGGER ledger_entries_no_update');
    DB::table('ledger_entries')->where('seq', 2)->update(['payload' => '{"amount":1}']);

    expect(app(Ledger::class)->firstBrokenSeq())->toBe(2);
    $this->artisan('ledger:verify')->assertFailed();
});

test('verify passes on an intact chain', function () {
    seedLedger();

    $this->artisan('ledger:verify')->assertSuccessful();
});

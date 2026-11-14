<?php

use App\Enums\LedgerEntryType;
use App\Models\LedgerAnchor;
use App\Models\LedgerEntry;
use App\Services\Ledger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function appendAnchorTestPayments(int ...$amounts): void
{
    foreach ($amounts as $amount) {
        app(Ledger::class)->append(LedgerEntryType::PaymentConfirmed, ['amount' => $amount]);
    }
}

/**
 * What someone with database access can do: wipe the chain and replay another history through the app,
 * which produces a chain that verifies on its own.
 */
function rebuildAnchorTestChain(int ...$amounts): void
{
    DB::statement('DROP TRIGGER ledger_entries_no_delete');
    DB::table('ledger_entries')->delete();
    appendAnchorTestPayments(...$amounts);
}

test('anchor is skipped while the ledger is empty', function () {
    expect(app(Ledger::class)->anchor())->toBeNull();

    $this->artisan('ledger:anchor')->assertSuccessful();

    expect(LedgerAnchor::count())->toBe(0);
});

test('anchor records the chain head once per day', function () {
    appendAnchorTestPayments(10000, 20000);
    $ledger = app(Ledger::class);

    $anchor = $ledger->anchor();
    appendAnchorTestPayments(5000);

    expect($ledger->anchor()->is($anchor))->toBeTrue()
        ->and(LedgerAnchor::count())->toBe(1)
        ->and($anchor->seq)->toBe(2)
        ->and($anchor->hash)->toBe(LedgerEntry::find(2)->hash)
        ->and($anchor->entry_count)->toBe(2);

    $this->travel(1)->days();
    $this->artisan('ledger:anchor')->assertSuccessful();

    expect(LedgerAnchor::count())->toBe(2)
        ->and(LedgerAnchor::latest('anchored_on')->first()->seq)->toBe(3);
});

test('anchors refuse update and delete', function () {
    appendAnchorTestPayments(10000);
    $anchor = app(Ledger::class)->anchor();

    expect(fn () => $anchor->update(['hash' => str_repeat('0', 64)]))->toThrow(LogicException::class)
        ->and(fn () => $anchor->delete())->toThrow(LogicException::class)
        ->and(fn () => DB::table('ledger_anchors')->update(['seq' => 9]))->toThrow(Exception::class)
        ->and(fn () => DB::table('ledger_anchors')->delete())->toThrow(Exception::class);
});

test('a rebuilt chain still verifies on its own but no longer matches its anchor', function () {
    appendAnchorTestPayments(10000, 20000, 30000);
    $anchor = app(Ledger::class)->anchor();

    rebuildAnchorTestChain(1000, 2000, 3000);

    expect(app(Ledger::class)->firstBrokenSeq())->toBeNull()
        ->and(app(Ledger::class)->firstMismatchedAnchor()?->is($anchor))->toBeTrue();

    $this->artisan('ledger:verify')
        ->expectsOutputToContain('Anchor '.$anchor->anchored_on->toDateString())
        ->assertFailed();

    $this->get('/')
        ->assertOk()
        ->assertSee('Terputus')
        ->assertSee('Anchor harian tidak cocok')
        ->assertDontSee('Rantai hash SHA-256 valid');

    $this->getJson('/api/v1/public/summary')->assertJsonPath('data.anchors_intact', false);
});

test('verify fails when an anchored entry no longer exists', function () {
    appendAnchorTestPayments(10000, 20000, 30000);
    app(Ledger::class)->anchor();

    DB::statement('DROP TRIGGER ledger_entries_no_delete');
    DB::table('ledger_entries')->where('seq', 3)->delete();

    expect(app(Ledger::class)->firstBrokenSeq())->toBeNull();

    $this->artisan('ledger:verify')
        ->expectsOutputToContain('(entry #3) no longer matches')
        ->assertFailed();
});

test('public api lists anchors newest first and reports the latest one', function () {
    $this->getJson('/api/v1/public/summary')
        ->assertJsonPath('data.latest_anchor', null)
        ->assertJsonPath('data.anchors_intact', true);

    appendAnchorTestPayments(10000);
    app(Ledger::class)->anchor();
    $this->travel(1)->days();
    appendAnchorTestPayments(20000);
    app(Ledger::class)->anchor();
    $latest = ['date' => now()->toDateString(), 'seq' => 2, 'hash' => LedgerEntry::find(2)->hash];

    $this->getJson('/api/v1/public/anchors')
        ->assertOk()
        ->assertJsonStructure(['data' => [['date', 'seq', 'hash']], 'links', 'meta'])
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0', $latest)
        ->assertJsonPath('data.1.seq', 1);

    $this->getJson('/api/v1/public/summary')
        ->assertJsonPath('data.latest_anchor', $latest)
        ->assertJsonPath('data.anchors_intact', true);

    $this->getJson('/api/v1/public/anchors?per_page=500')->assertUnprocessable();
});

test('public dashboard shows the latest anchor for readers to keep', function () {
    appendAnchorTestPayments(10000, 20000);
    $anchor = app(Ledger::class)->anchor();

    $this->get('/')->assertOk();
    $page = preg_replace('/\s+/', ' ', strip_tags($this->get('/')->getContent()));

    expect($page)->toContain('Anchor harian', 'entri #2', 'tangkap layar', 'Rantai hash SHA-256 valid')
        ->toContain(substr($anchor->hash, 0, 4).'…'.substr($anchor->hash, -4))
        ->toContain($anchor->hash);
});

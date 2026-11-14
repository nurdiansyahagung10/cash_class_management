<?php

use App\Enums\LedgerEntryType;
use App\Models\User;
use App\Services\Ledger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.notion.token' => 'secret-token',
        'services.notion.ledger_database_id' => 'db-123',
        'services.notion.version' => '2022-06-28',
    ]);
});

function appendPayments(int ...$amounts): void
{
    foreach ($amounts as $amount) {
        app(Ledger::class)->append(LedgerEntryType::PaymentConfirmed, ['amount' => $amount, 'category' => 'Uang Kas']);
    }
}

test('skips without calling Notion when the token or database id is missing', function () {
    Http::preventStrayRequests();
    config(['services.notion.token' => null]);
    appendPayments(10000);

    $this->artisan('notion:sync')
        ->expectsOutputToContain('skipped')
        ->assertSuccessful();

    Http::assertNothingSent();
});

test('reads the last synced seq from Notion and sends only newer entries in order', function () {
    Sleep::fake();
    Http::preventStrayRequests();
    Http::fake([
        'api.notion.com/v1/databases/db-123/query' => Http::response(['results' => [['properties' => ['Seq' => ['number' => 1]]]]]),
        'api.notion.com/v1/pages' => Http::response(['object' => 'page']),
    ]);
    appendPayments(10000, 20000, 30000);

    $this->artisan('notion:sync')->assertSuccessful();

    $page = fn (int $seq) => fn (Request $request) => $request->url() === 'https://api.notion.com/v1/pages'
        && $request->hasHeader('Authorization', 'Bearer secret-token')
        && $request->hasHeader('Notion-Version', '2022-06-28')
        && $request['parent']['database_id'] === 'db-123'
        && $request['properties']['Seq']['number'] === $seq
        && $request['properties']['Nama']['title'][0]['text']['content'] === "#{$seq} PAYMENT_CONFIRMED"
        && $request['properties']['Tipe']['select']['name'] === 'PAYMENT_CONFIRMED'
        && $request['properties']['Jumlah']['number'] === $seq * 10000
        && $request['properties']['Kategori']['rich_text'][0]['text']['content'] === 'Uang Kas';

    Http::assertSentInOrder([
        fn (Request $request) => $request->url() === 'https://api.notion.com/v1/databases/db-123/query'
            && $request['sorts'] === [['property' => 'Seq', 'direction' => 'descending']]
            && $request['page_size'] === 1,
        $page(2),
        $page(3),
    ]);
});

test('starts from the first entry and honours --limit when Notion is empty', function () {
    Sleep::fake();
    Http::preventStrayRequests();
    Http::fake([
        'api.notion.com/v1/databases/db-123/query' => Http::response(['results' => []]),
        'api.notion.com/v1/pages' => Http::response(['object' => 'page']),
    ]);
    appendPayments(10000, 20000, 30000);

    $this->artisan('notion:sync', ['--limit' => 2])->assertSuccessful();

    $sentSeqs = Http::recorded(fn (Request $request) => $request->url() === 'https://api.notion.com/v1/pages')
        ->map(fn (array $pair) => $pair[0]['properties']['Seq']['number'])
        ->values()
        ->all();
    expect($sentSeqs)->toBe([1, 2]);
});

test('never sends names, student numbers or staff names to Notion', function () {
    Sleep::fake();
    Http::preventStrayRequests();
    Http::fake([
        'api.notion.com/v1/databases/db-123/query' => Http::response(['results' => []]),
        'api.notion.com/v1/pages' => Http::response(['object' => 'page']),
    ]);
    $staff = User::factory()->create(['name' => 'Siti Bendahara']);
    app(Ledger::class)->append(LedgerEntryType::PaymentConfirmed, [
        'amount' => 10000,
        'category' => 'Uang Kas',
        'name' => 'Budi Rahasia',
        'student_no' => '2024999',
        'paid_by_staff_name' => 'Rina Staff',
    ], $staff);

    $this->artisan('notion:sync')->assertSuccessful();

    $bodies = Http::recorded()->map(fn (array $pair) => $pair[0]->body())->implode('');
    expect($bodies)
        ->toContain('Uang Kas', 'Pengurus kelas')
        ->not->toContain('Budi Rahasia', '2024999', 'Siti Bendahara', 'Rina Staff');
});

test('retries a rate limited request after the Retry-After delay', function () {
    Sleep::fake();
    Http::preventStrayRequests();
    Http::fake([
        'api.notion.com/v1/databases/db-123/query' => Http::response(['results' => []]),
        'api.notion.com/v1/pages' => Http::sequence()
            ->push(['code' => 'rate_limited'], 429, ['Retry-After' => '2'])
            ->push(['object' => 'page']),
    ]);
    appendPayments(10000);

    $this->artisan('notion:sync')->assertSuccessful();

    Http::assertSentCount(3);
    Sleep::assertSlept(fn ($duration) => $duration->totalMilliseconds === 2000.0);
});

test('stops at the first failed request, prints the required properties and fails', function () {
    Sleep::fake();
    Http::preventStrayRequests();
    Http::fake([
        'api.notion.com/v1/databases/db-123/query' => Http::response(['results' => []]),
        'api.notion.com/v1/pages' => Http::sequence()
            ->push(['object' => 'page'])
            ->push(['code' => 'validation_error', 'message' => 'Seq is not a property that exists.'], 400),
    ]);
    appendPayments(10000, 20000, 30000);

    $this->artisan('notion:sync')
        ->expectsOutputToContain('stopped while sending entry #2: HTTP 400 Seq is not a property that exists.')
        ->expectsOutputToContain('Hash sebelumnya')
        ->assertFailed();

    Http::assertSentCount(3);
});

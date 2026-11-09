<?php

use App\Enums\LedgerEntryType;
use App\Models\User;
use App\Services\Ledger;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('public summary returns cash aggregates without login', function () {
    User::factory()->create();
    app(Ledger::class)->append(LedgerEntryType::PaymentConfirmed, ['amount' => 20000]);

    $this->getJson('/api/v1/public/summary')
        ->assertOk()
        ->assertJsonPath('data.balance', 20000)
        ->assertJsonPath('data.balance_formatted', 'Rp20.000')
        ->assertJsonPath('data.ledger.intact', true)
        ->assertJsonPath('data.ledger.total_entries', 1);
});

test('public ledger api hides personal data', function () {
    $staff = User::factory()->create(['name' => 'Siti Bendahara']);
    app(Ledger::class)->append(LedgerEntryType::PaymentConfirmed, [
        'amount' => 10000,
        'name' => 'Budi Rahasia',
        'student_no' => '2024999',
        'category' => 'Uang Kas',
    ], $staff);

    $response = $this->getJson('/api/v1/public/ledger')
        ->assertOk()
        ->assertJsonPath('data.0.seq', 1)
        ->assertJsonPath('data.0.amount', 10000)
        ->assertJsonPath('data.0.category', 'Uang Kas')
        ->assertJsonPath('data.0.recorded_by', 'Pengurus kelas')
        ->assertJsonMissingPath('data.0.payload.name')
        ->assertJsonMissingPath('data.0.payload.student_no');

    expect($response->getContent())->not->toContain('Budi Rahasia', '2024999', 'Siti Bendahara');
});

test('public ledger api returns only entries after since_seq', function () {
    foreach ([1000, 2000, 3000] as $amount) {
        app(Ledger::class)->append(LedgerEntryType::PaymentConfirmed, ['amount' => $amount]);
    }

    $this->getJson('/api/v1/public/ledger?since_seq=1')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.seq', 2);

    $this->getJson('/api/v1/public/ledger?per_page=500')->assertUnprocessable();
});

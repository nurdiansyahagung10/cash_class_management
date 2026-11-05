<?php

use App\Enums\LedgerEntryType;
use App\Models\User;
use App\Services\Ledger;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can stream export ledger entries as csv', function () {
    $user = User::factory()->create();
    $ledger = app(Ledger::class);

    $ledger->append(LedgerEntryType::AccountVerified, ['name' => 'Budi'], $user);
    $ledger->append(LedgerEntryType::PaymentConfirmed, ['amount' => 10000]);

    $response = $this->actingAs($user)->get(route('ledger.export'));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    $content = $response->streamedContent();

    expect($content)->toContain('Seq,Tipe')
        ->and($content)->toContain('ACCOUNT_VERIFIED')
        ->and($content)->toContain('PAYMENT_CONFIRMED');
});

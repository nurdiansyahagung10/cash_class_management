<?php

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('opening a payment page reuses existing pending payment instead of creating another', function () {
    $user = User::factory()->create();
    $invoice = Invoice::factory()->create(['user_id' => $user->id]);
    Payment::factory()->create(['invoice_id' => $invoice->id, 'status' => 'pending', 'order_id' => 'OLD-PAYMENT']);

    $this->actingAs($user)->get(route('payments.create', $invoice))->assertOk();

    expect(Payment::where('invoice_id', $invoice->id)->count())->toBe(1);
});

test('dashboard shows only the latest payment for each invoice', function () {
    $user = User::factory()->create();
    $invoice = Invoice::factory()->create(['user_id' => $user->id]);
    Payment::factory()->create(['invoice_id' => $invoice->id, 'status' => 'pending', 'order_id' => 'OLD-PAYMENT']);
    Payment::factory()->create(['invoice_id' => $invoice->id, 'status' => 'pending', 'order_id' => 'CURRENT-PAYMENT']);

    $latestPayment = Payment::where('order_id', 'CURRENT-PAYMENT')->firstOrFail();

    $this->actingAs($user)->get('/')
        ->assertOk()
        ->assertSee('Riwayat pembayaran')
        ->assertViewHas('latestPayments', fn ($payments) => $payments->count() === 1 && $payments->sole()->is($latestPayment));
});

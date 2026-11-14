<?php

use App\Models\BillingPeriod;
use App\Models\Invoice;
use App\Models\User;
use App\Services\PaymentReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function reminderPeriod(array $attributes = []): BillingPeriod
{
    return BillingPeriod::factory()->create([
        'title' => 'Kas Pekan 7',
        'amount' => 15000,
        'due_date' => '2099-03-05',
        ...$attributes,
    ]);
}

it('writes a personal message with name, remaining amount, due date and pay link', function () {
    $invoice = Invoice::factory()->partial()->create([
        'billing_period_id' => reminderPeriod()->id,
        'user_id' => User::factory()->create(['name' => 'Citra Lestari']),
        'amount' => 15000,
    ]);
    $invoice->payments()->create(['order_id' => 'KAS-T1', 'status' => 'paid', 'amount' => 5000, 'qr_content' => 'x']);

    $message = app(PaymentReminder::class)->personalMessage($invoice->fresh(['user', 'period', 'payments']));

    expect($message)
        ->toContain('Halo Citra Lestari')
        ->toContain('Rp10.000')
        ->toContain('5 Maret 2099')
        ->toContain(route('payments.create', $invoice))
        ->not->toContain('melewati jatuh tempo');
});

it('asks for full payment once the due date has passed', function () {
    $invoice = Invoice::factory()->create([
        'billing_period_id' => reminderPeriod(['due_date' => '2020-01-10'])->id,
        'amount' => 15000,
    ]);

    $message = app(PaymentReminder::class)->personalMessage($invoice->fresh(['user', 'period', 'payments']));

    expect($message)
        ->toContain('sudah melewati jatuh tempo (10 Januari 2020)')
        ->toContain('Rp15.000');
});

it('lists only unpaid and partial members in the group message', function () {
    $period = reminderPeriod();
    foreach ([['Adit Pratama', 'paid'], ['Bela Safitri', 'unpaid'], ['Dewi Anggraini', 'partial']] as [$name, $status]) {
        Invoice::factory()->create([
            'billing_period_id' => $period->id,
            'user_id' => User::factory()->create(['name' => $name]),
            'amount' => 15000,
            'status' => $status,
        ]);
    }
    $admin = User::factory()->admin()->create();

    $page = $this->actingAs($admin)->get(route('periods.show', $period))->assertOk();
    $message = $page->viewData('reminder')->groupMessage($period, $page->viewData('unpaidInvoices'));

    expect($message)
        ->toContain('Kas Pekan 7')
        ->toContain('5 Maret 2099')
        ->toContain('Bela Safitri (sisa Rp15.000)')
        ->toContain('Dewi Anggraini')
        ->toContain(route('dashboard'))
        ->not->toContain('Adit Pratama');
});

it('shows reminder buttons only for unpaid members and hides the section when everyone paid', function () {
    $admin = User::factory()->admin()->create();
    $period = reminderPeriod();
    Invoice::factory()->paid()->create([
        'billing_period_id' => $period->id,
        'user_id' => User::factory()->create(['name' => 'Adit Pratama']),
    ]);

    $this->actingAs($admin)->get(route('periods.show', $period))
        ->assertOk()
        ->assertDontSee('Pengingat pembayaran');

    Invoice::factory()->create([
        'billing_period_id' => $period->id,
        'user_id' => User::factory()->create(['name' => 'Bela Safitri']),
    ]);

    $this->actingAs($admin)->get(route('periods.show', $period))
        ->assertOk()
        ->assertSee('Pengingat pembayaran')
        ->assertSee('Salin pesan grup')
        ->assertSee('Salin pesan');
});

it('builds a wa.me link only when the member has a phone number', function (?string $phone, ?string $expectedNumber) {
    $invoice = Invoice::factory()->create([
        'billing_period_id' => reminderPeriod()->id,
        'user_id' => User::factory()->create(['phone' => $phone]),
    ]);

    $url = app(PaymentReminder::class)->whatsAppUrl($invoice->fresh(['user', 'period', 'payments']));

    if ($expectedNumber === null) {
        expect($url)->toBeNull();

        return;
    }

    expect($url)->toStartWith("https://wa.me/{$expectedNumber}?text=Halo%20");
})->with([
    'leading zero' => ['0812-3456-7890', '6281234567890'],
    'plus 62' => ['+62 812 3456 7890', '6281234567890'],
    'plain 62' => ['6281234567890', '6281234567890'],
    'no phone' => [null, null],
]);

it('keeps plain members out of the period page', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('periods.show', reminderPeriod()))
        ->assertRedirect(route('dashboard'));
});

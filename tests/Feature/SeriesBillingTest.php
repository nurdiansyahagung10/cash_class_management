<?php

use App\Enums\LedgerEntryType;
use App\Models\BillingPeriod;
use App\Models\Invoice;
use App\Models\LedgerEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function seriesPayload(array $overrides = []): array
{
    return [
        'title' => 'Kas November',
        'amount' => 5000,
        'installments' => 4,
        'installment_unit' => 'week',
        'starts_on' => '2026-11-04',
        'qris_url' => 'https://example.test/qris.png',
        ...$overrides,
    ];
}

test('a weekly series creates one bill per week, each due Sunday night, with its own ledger entry', function () {
    $this->travelTo('2026-11-04 03:00:00');
    $admin = User::factory()->admin()->create();
    User::factory()->count(2)->create();

    $this->actingAs($admin)->post('/periods', seriesPayload())->assertRedirect();

    $periods = BillingPeriod::orderBy('id')->get();

    expect($periods->pluck('title')->all())->toBe(['Kas November (1/4)', 'Kas November (2/4)', 'Kas November (3/4)', 'Kas November (4/4)'])
        ->and($periods->map(fn (BillingPeriod $period) => $period->due_date->toDateString())->all())
        ->toBe(['2026-11-08', '2026-11-15', '2026-11-22', '2026-11-29'])
        ->and($periods->pluck('amount')->unique()->all())->toBe([5000])
        ->and(Invoice::count())->toBe(8)
        ->and(LedgerEntry::where('type', LedgerEntryType::InvoiceCreated)->count())->toBe(4)
        ->and(LedgerEntry::orderBy('seq')->first()->payload)->toContain('"series":"1/4"');
});

test('a total can be split evenly, with the remainder on the last bill', function () {
    $this->travelTo('2026-11-04 03:00:00');
    $admin = User::factory()->admin()->create();
    User::factory()->create();

    $this->actingAs($admin)->post('/periods', seriesPayload([
        'amount' => 10000,
        'installments' => 3,
        'amount_mode' => 'total',
    ]))->assertRedirect();

    expect(BillingPeriod::orderBy('id')->pluck('amount')->all())->toBe([3333, 3333, 3334]);
});

test('a monthly series is due on the last day of each month', function () {
    $this->travelTo('2026-11-04 03:00:00');
    $admin = User::factory()->admin()->create();
    User::factory()->create();

    $this->actingAs($admin)->post('/periods', seriesPayload(['installments' => 3, 'installment_unit' => 'month']))->assertRedirect();

    expect(BillingPeriod::orderBy('id')->get()->map(fn (BillingPeriod $period) => $period->due_date->toDateString())->all())
        ->toBe(['2026-11-30', '2026-12-31', '2027-01-31']);
});

test('a series is refused when a bill would fall under the minimum or has no unit', function () {
    $this->travelTo('2026-11-04 03:00:00');
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post('/periods', seriesPayload(['amount' => 3000, 'installments' => 4, 'amount_mode' => 'total']))
        ->assertSessionHasErrors('amount');

    $this->actingAs($admin)->post('/periods', seriesPayload(['installment_unit' => '']))
        ->assertSessionHasErrors('installment_unit');

    expect(BillingPeriod::count())->toBe(0);
});

test('a bill stays on time until 23:59:59 WIB on its due date', function () {
    $period = BillingPeriod::factory()->create(['due_date' => '2026-11-08']);
    $invoice = Invoice::factory()->create(['billing_period_id' => $period->id]);

    $this->travelTo('2026-11-08 16:59:30');
    expect($invoice->fresh()->isOverdue())->toBeFalse();

    $this->travelTo('2026-11-08 17:00:30');
    expect($invoice->fresh()->isOverdue())->toBeTrue();
});

test('the new bill form offers splitting into several payments', function () {
    $this->actingAs(User::factory()->admin()->create())->get('/periods')
        ->assertOk()
        ->assertSee('Bagi jadi beberapa pembayaran')
        ->assertSee('Total, dibagi rata');
});

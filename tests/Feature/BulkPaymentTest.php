<?php

use App\Enums\LedgerEntryType;
use App\Models\BillingPeriod;
use App\Models\Invoice;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\User;
use App\Services\Ledger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/**
 * One member with a bill per week: the first one already overdue, the rest still ahead.
 *
 * @return array{0: User, 1: Collection<int, Invoice>}
 */
function memberWithWeeklyBills(int $weeks = 3): array
{
    $member = User::factory()->create();
    $invoices = collect(range(1, $weeks))->map(function (int $week) use ($member) {
        $period = BillingPeriod::factory()->create([
            'title' => "Kas minggu {$week}",
            'amount' => 5000,
            'due_date' => now()->addWeeks($week - 2)->toDateString(),
            'qris_url' => 'https://example.test/qris.png',
        ]);

        return Invoice::factory()->create(['user_id' => $member->id, 'billing_period_id' => $period->id, 'amount' => 5000]);
    });

    return [$member, $invoices];
}

/**
 * @param  Collection<int, Invoice>  $invoices
 */
function submitBulk(User $actor, $invoices, ?Invoice $anchor = null)
{
    return test()->actingAs($actor)->post(route('payments.bulk.submit', $anchor ?? $invoices->first()), [
        'ids' => $invoices->pluck('id')->all(),
        'proof' => UploadedFile::fake()->image('bukti.jpg'),
    ]);
}

test('the combined page totals the selected bills, overdue ones included', function () {
    [$member, $invoices] = memberWithWeeklyBills();

    $this->actingAs($member)
        ->get(route('payments.bulk', ['invoice' => $invoices->first(), 'ids' => $invoices->pluck('id')->all()]))
        ->assertOk()
        ->assertSee('Total 3 tagihan')
        ->assertSee('Rp15.000')
        ->assertSee('Lewat tenggat');
});

test('one proof creates a pending payment per bill that share a batch and the file', function () {
    Storage::fake();
    [$member, $invoices] = memberWithWeeklyBills();

    submitBulk($member, $invoices)->assertRedirect(route('dashboard'))->assertSessionHas('status');

    $payments = Payment::orderBy('id')->get();

    expect($payments)->toHaveCount(3)
        ->and($payments->pluck('batch_key')->unique())->toHaveCount(1)
        ->and($payments->pluck('batch_key')->first())->not->toBeNull()
        ->and($payments->pluck('proof_path')->unique())->toHaveCount(1)
        ->and($payments->pluck('amount')->all())->toBe([5000, 5000, 5000])
        ->and($payments->pluck('status')->unique()->all())->toBe(['pending']);

    Storage::assertExists($payments->first()->proof_path);
});

test('a combined payment cannot include another member\'s bill or a bill already paid', function () {
    Storage::fake();
    [$member, $invoices] = memberWithWeeklyBills();
    $stranger = Invoice::factory()->create();
    $paid = Invoice::factory()->paid()->create(['user_id' => $member->id]);

    $this->actingAs($member)->post(route('payments.bulk.submit', $invoices->first()), [
        'ids' => [$invoices->first()->id, $stranger->id],
        'proof' => UploadedFile::fake()->image('bukti.jpg'),
    ])->assertSessionHasErrors('ids');

    $this->actingAs($member)->post(route('payments.bulk.submit', $invoices->first()), [
        'ids' => [$paid->id],
        'proof' => UploadedFile::fake()->image('bukti.jpg'),
    ])->assertSessionHasErrors('ids');

    $this->actingAs(User::factory()->create())->get(route('payments.bulk', $invoices->first()))->assertForbidden();

    expect(Payment::count())->toBe(0);
});

test('approving one payment of a batch verifies every bill with its own ledger entry', function () {
    Storage::fake();
    [$member, $invoices] = memberWithWeeklyBills();
    submitBulk($member, $invoices);

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('payments.approve', Payment::orderBy('id')->first()))
        ->assertRedirect();

    expect(Invoice::where('status', 'paid')->count())->toBe(3)
        ->and(LedgerEntry::where('type', LedgerEntryType::PaymentConfirmed)->count())->toBe(3)
        ->and(app(Ledger::class)->balance())->toBe(15000)
        ->and(app(Ledger::class)->firstBrokenSeq())->toBeNull();
});

test('rejecting a batch clears every row and deletes the shared file once', function () {
    Storage::fake();
    [$member, $invoices] = memberWithWeeklyBills();
    submitBulk($member, $invoices);
    $path = Payment::first()->proof_path;

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('payments.reject', Payment::orderBy('id')->first()), ['rejection_note' => 'Buram'])
        ->assertRedirect();

    expect(Payment::whereNotNull('proof_path')->count())->toBe(0)
        ->and(Payment::whereNotNull('batch_key')->count())->toBe(0)
        ->and(Payment::where('rejection_note', 'Buram')->count())->toBe(3)
        ->and(Payment::where('status', 'pending')->count())->toBe(3);

    Storage::assertMissing($path);
});

test('the review list shows one card per batch, and single flows leave a batch untouched', function () {
    Storage::fake();
    [$member, $invoices] = memberWithWeeklyBills();
    submitBulk($member, $invoices);

    $review = $this->actingAs(User::factory()->admin()->create())->get(route('payments.review'))->assertOk()->assertSee('Dibayar sekaligus');
    expect(substr_count($review->getContent(), 'Verifikasi lunas'))->toBe(1);

    $second = $invoices->get(1);
    $this->actingAs($member)->get(route('payments.create', $second))->assertRedirect(route('dashboard'));
    $this->actingAs($member)->post(route('payments.installment', $second), ['amount' => 1000])->assertSessionHas('status');
    $this->actingAs($member)->post(route('payments.proof', $second), ['proof' => UploadedFile::fake()->image('lain.jpg')])->assertSessionHas('status');

    expect(Payment::where('invoice_id', $second->id)->count())->toBe(1)
        ->and(Payment::where('invoice_id', $second->id)->value('amount'))->toBe(5000);

    $this->actingAs($member)->get(route('payments.bulk', $invoices->first()))->assertSee('Tidak ada tagihan yang bisa dibayar');
});

test('the dashboard and the single pay page link to the combined payment', function () {
    [$member, $invoices] = memberWithWeeklyBills();

    $this->actingAs($member)->get(route('dashboard'))->assertOk()->assertSee('Bayar 3 tagihan sekaligus');
    $this->actingAs($member)->get(route('payments.create', $invoices->first()))->assertOk()->assertSee('Bayar 3 tagihan sekaligus dengan satu bukti');
});

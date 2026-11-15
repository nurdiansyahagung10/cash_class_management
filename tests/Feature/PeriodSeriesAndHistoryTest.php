<?php

use App\Models\BillingPeriod;
use App\Models\Category;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

const QRIS = '00020101021226610014COM.EXAMPLE.WWW';

beforeEach(function () {
    // Wednesday 25 Nov 2026, so its Monday-Sunday week is 23-29 Nov.
    $this->travelTo(now(BillingPeriod::TIMEZONE)->setDate(2026, 11, 25)->setTime(10, 0));
});

test('cash category needs no name and becomes a weekly series named Kas Minggu ke N', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->count(2)->create();
    $cash = Category::firstOrCreate(['name' => 'Uang Kas']);

    $this->actingAs($admin)->post(route('periods.store'), [
        'category_id' => $cash->id,
        'amount' => 5000,
        'installments' => 2,
        'installment_unit' => 'month',
        'amount_mode' => 'total',
        'starts_on' => '2026-11-25',
        'qris_payload' => QRIS,
    ])->assertSessionHasNoErrors();

    $periods = BillingPeriod::orderBy('id')->get();

    expect($periods->map->getRawOriginal('title')->all())->toBe(['', ''])
        ->and($periods->pluck('title')->all())->toBe(['Kas Minggu ke 1', 'Kas Minggu ke 2'])
        ->and($periods->pluck('amount')->all())->toBe([5000, 5000])
        ->and($periods[0]->starts_on->toDateString())->toBe('2026-11-23')
        ->and($periods[0]->due_date->toDateString())->toBe('2026-11-29')
        ->and($periods[1]->starts_on->toDateString())->toBe('2026-11-30')
        ->and($periods[1]->due_date->toDateString())->toBe('2026-12-06');
});

test('cash weeks are numbered by creation order, not stored', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create();
    $cash = Category::firstOrCreate(['name' => 'Uang Kas']);
    BillingPeriod::factory()->count(2)->create(['title' => '', 'category_id' => $cash->id, 'created_by' => $admin->id]);

    $this->actingAs($admin)->post(route('periods.store'), [
        'category_id' => $cash->id,
        'amount' => 5000,
        'starts_on' => '2026-11-25',
        'qris_payload' => QRIS,
    ])->assertSessionHasNoErrors();

    $period = BillingPeriod::latest('id')->first();

    expect($period->getRawOriginal('title'))->toBe('')
        ->and($period->title)->toBe('Kas Minggu ke 3')
        ->and($period->due_date->toDateString())->toBe('2026-11-29');
});

test('other categories still need a name and keep their own schedule', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create();
    $other = Category::firstOrCreate(['name' => 'Jaket']);

    $this->actingAs($admin)->post(route('periods.store'), [
        'category_id' => $other->id,
        'amount' => 5000,
        'due_date' => '2026-12-01',
        'qris_payload' => QRIS,
    ])->assertSessionHasErrors('title');

    $this->actingAs($admin)->post(route('periods.store'), [
        'category_id' => $other->id,
        'title' => 'Jaket Angkatan',
        'amount' => 5000,
        'due_date' => '2026-12-01',
        'qris_payload' => QRIS,
    ])->assertSessionHasNoErrors();

    expect(BillingPeriod::sole()->title)->toBe('Jaket Angkatan');
});

test('period page asks for the category before the name', function () {
    $admin = User::factory()->admin()->create();
    Category::firstOrCreate(['name' => 'Uang Kas']);

    $html = $this->actingAs($admin)->get(route('periods.index'))->assertOk()->getContent();

    expect(strpos($html, 'Kategori Tagihan'))->toBeLessThan(strpos($html, 'Nama Periode Tagihan'));
});

test('member history groups bills into incoming, running and paid', function () {
    $member = User::factory()->create();
    $admin = User::factory()->admin()->create();

    $make = fn (string $title, string $start, string $due, string $status = 'unpaid') => Invoice::factory()->create([
        'user_id' => $member->id,
        'status' => $status,
        'billing_period_id' => BillingPeriod::factory()->create([
            'title' => $title, 'starts_on' => $start, 'due_date' => $due, 'created_by' => $admin->id,
        ]),
    ]);

    $make('Kas Minggu ke 2', '2026-11-30', '2026-12-06');
    $make('Kas Minggu ke 1', '2026-11-23', '2026-11-29');
    $paid = $make('Kas Minggu ke 0', '2026-11-16', '2026-11-22', 'paid');
    Payment::factory()->create(['invoice_id' => $paid->id, 'status' => 'paid', 'paid_at' => now()]);

    $this->actingAs($member)->get(route('payments.history'))
        ->assertOk()
        ->assertSeeInOrder(['Akan datang', 'Kas Minggu ke 2', 'Berjalan', 'Kas Minggu ke 1', 'Sudah dibayar', 'Kas Minggu ke 0'])
        ->assertViewHas('groups', fn ($g) => $g['incoming']->count() === 1 && $g['running']->count() === 1 && $g['paid']->count() === 1);

    $this->actingAs($member)->get(route('payments.history', ['tahap' => 'paid']))
        ->assertOk()
        ->assertDontSee('Kas Minggu ke 2');
});

test('history shows only the member\'s own bills and sends admins home', function () {
    $member = User::factory()->create();
    Invoice::factory()->create(['billing_period_id' => BillingPeriod::factory()->create(['title' => 'Milik Orang Lain'])]);

    $this->actingAs($member)->get(route('payments.history'))->assertOk()->assertDontSee('Milik Orang Lain');
    $this->actingAs(User::factory()->admin()->create())->get(route('payments.history'))->assertRedirect(route('dashboard'));
    $this->get(route('payments.history'))->assertRedirect();
});

test('cash week names show on the dashboard, the bill list and its search', function () {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create();
    $cash = Category::firstOrCreate(['name' => 'Uang Kas']);
    $period = BillingPeriod::factory()->create(['title' => '', 'category_id' => $cash->id, 'created_by' => $admin->id]);
    Invoice::factory()->create(['user_id' => $member->id, 'billing_period_id' => $period->id]);

    $this->actingAs($member)->get(route('dashboard'))->assertOk()->assertSee('Kas Minggu ke 1');
    $this->actingAs($admin)->get(route('dashboard'))->assertOk()->assertSee('Kas Minggu ke 1');
    $this->actingAs($admin)->get(route('periods.index', ['q' => 'minggu ke 1']))->assertOk()->assertSee('Kas Minggu ke 1');
    $this->actingAs($admin)->get(route('periods.show', $period))->assertOk()->assertSee('Kas Minggu ke 1');
});

<?php

use App\Enums\LedgerEntryType;
use App\Models\LedgerEntry;
use App\Models\PendingMember;
use App\Models\User;
use App\Services\Ledger;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('dashboard shows the balance derived from the ledger', function () {
    $ledger = app(Ledger::class);
    $ledger->append(LedgerEntryType::PaymentConfirmed, ['amount' => 10000]);
    $ledger->append(LedgerEntryType::PaymentConfirmed, ['amount' => 5000]);
    $ledger->append(LedgerEntryType::ExpenseApproved, ['amount' => 2500]);

    $this->actingAs(User::factory()->create())->get('/')
        ->assertOk()
        ->assertSee('Rp12.500')
        ->assertSee('Utuh');
});

test('ledger page lists entries and reports integrity', function () {
    app(Ledger::class)->append(LedgerEntryType::PaymentConfirmed, ['amount' => 10000]);

    $this->actingAs(User::factory()->create())->get('/ledger')
        ->assertOk()
        ->assertSee('PAYMENT_CONFIRMED')
        ->assertSee('1 entri valid');
});

test('ledger page reports a broken chain', function () {
    app(Ledger::class)->append(LedgerEntryType::PaymentConfirmed, ['amount' => 10000]);
    DB::statement('DROP TRIGGER ledger_entries_no_update');
    DB::table('ledger_entries')->update(['payload' => '{"amount":1}']);

    $this->actingAs(User::factory()->create())->get('/ledger')->assertSee('Putus di entri #1');
});

test('ledger search filters by type', function () {
    app(Ledger::class)->append(LedgerEntryType::PaymentConfirmed, ['amount' => 10000]);
    app(Ledger::class)->append(LedgerEntryType::Reversal, ['of' => 1]);

    $this->actingAs(User::factory()->create())->get('/ledger?q=REVERSAL')
        ->assertSee('REVERSAL')
        ->assertDontSee('PAYMENT_CONFIRMED');
});

test('plain members cannot open the verification page', function () {
    $this->actingAs(User::factory()->create())->get('/members')->assertForbidden();
});

test('staff add a candidate who then shows in the queue', function () {
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->post('/members', ['name' => 'Rina Kusuma', 'student_no' => '29', 'password' => 'initial-123'])
        ->assertRedirect();

    $this->actingAs($staff)->get('/members')->assertOk()->assertSee('Rina Kusuma');
});

test('a duplicate student number is rejected in the form', function () {
    $staff = User::factory()->staff()->create();
    PendingMember::factory()->create(['student_no' => '29']);

    $this->actingAs($staff)->post('/members', ['name' => 'Rina', 'student_no' => '29', 'password' => 'initial-123'])
        ->assertSessionHasErrors('student_no');
});

test('staff cannot approve', function () {
    $staff = User::factory()->staff()->create();
    $pending = PendingMember::factory()->create();

    $this->actingAs($staff)->post("/members/{$pending->id}/approve")->assertForbidden();
});

test('admin approves a candidate and the ledger records it', function () {
    $admin = User::factory()->admin()->create();
    $pending = PendingMember::factory()->create(['student_no' => '77']);

    $this->actingAs($admin)->post("/members/{$pending->id}/approve")->assertRedirect();

    expect(User::where('student_no', '77')->exists())->toBeTrue()
        ->and(LedgerEntry::sole()->type)->toBe(LedgerEntryType::AccountVerified);
});

test('admin rejects a candidate', function () {
    $admin = User::factory()->admin()->create();
    $pending = PendingMember::factory()->create();

    $this->actingAs($admin)->delete("/members/{$pending->id}")->assertRedirect();

    expect(PendingMember::count())->toBe(0)->and(LedgerEntry::count())->toBe(0);
});

test('staff can search members by name or student number', function () {
    $staff = User::factory()->staff()->create();
    User::factory()->create(['name' => 'Budi Pratama', 'student_no' => '101']);
    User::factory()->create(['name' => 'Siti Aisyah', 'student_no' => '102']);

    $this->actingAs($staff)->get(route('members.index', ['q' => 'Budi']))
        ->assertOk()
        ->assertSee('Budi Pratama')
        ->assertDontSee('Siti Aisyah');

    $this->actingAs($staff)->get(route('members.index', ['q' => '102']))
        ->assertOk()
        ->assertSee('Siti Aisyah')
        ->assertDontSee('Budi Pratama');
});

test('guests see the public ledger without personal data', function () {
    $staff = User::factory()->create(['name' => 'Siti Bendahara']);
    app(Ledger::class)->append(LedgerEntryType::PaymentConfirmed, [
        'amount' => 10000,
        'name' => 'Budi Rahasia',
        'student_no' => '2024999',
        'category' => 'Uang Kas',
    ], $staff);

    $this->get('/ledger')
        ->assertOk()
        ->assertSee('PAYMENT_CONFIRMED')
        ->assertSee('1 entri valid')
        ->assertSee('Uang Kas')
        ->assertSee('Pengurus kelas')
        ->assertDontSee('Budi Rahasia')
        ->assertDontSee('2024999')
        ->assertDontSee('Siti Bendahara')
        ->assertDontSee(route('ledger.export'));
});

test('guests cannot find ledger entries by searching a name', function () {
    app(Ledger::class)->append(LedgerEntryType::PaymentConfirmed, ['amount' => 10000, 'name' => 'Budi Rahasia']);

    $this->get('/ledger?q=Budi')->assertOk()->assertSee('Belum ada entri.');
});

test('guests cannot export or verify the ledger', function () {
    $this->get(route('ledger.export'))->assertRedirect(route('login'));
    $this->post(route('ledger.verify'))->assertRedirect(route('login'));
});

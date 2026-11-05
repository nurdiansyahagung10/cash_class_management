<?php

use App\Enums\LedgerEntryType;
use App\Models\BillingPeriod;
use App\Models\Invoice;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Services\Ledger;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin creates a period, invoicing every active member and writing to the ledger', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->count(2)->create();
    User::factory()->create(['is_active' => false]);

    $this->actingAs($admin)->post('/periods', [
        'title' => 'Kas minggu 9',
        'amount' => 10000,
        'due_date' => now()->addWeek()->toDateString(),
        'qris_url' => 'https://example.test/qris.png',
    ])->assertRedirect();

    $period = BillingPeriod::sole();
    $entry = LedgerEntry::sole();

    expect($period->invoices()->count())->toBe(3)
        ->and(Invoice::where('status', 'unpaid')->count())->toBe(3)
        ->and($entry->type)->toBe(LedgerEntryType::InvoiceCreated)
        ->and($entry->ref_id)->toBe($period->id)
        ->and(app(Ledger::class)->firstBrokenSeq())->toBeNull();
});

test('staff and admin can create periods with category, writing to ledger', function () {
    $staff = User::factory()->staff()->create();
    User::factory()->count(2)->create();

    $this->actingAs($staff)->post('/periods', [
        'title' => 'Jaket Angkatan Kelas 1A',
        'new_category' => 'Baju & Merchandise',
        'amount' => 150000,
        'due_date' => now()->addWeek()->toDateString(),
        'qris_url' => 'https://example.test/qris.png',
    ])->assertRedirect();

    $period = BillingPeriod::where('title', 'Jaket Angkatan Kelas 1A')->firstOrFail();
    $entry = LedgerEntry::sole();

    expect($period->category->name)->toBe('Baju & Merchandise')
        ->and($period->invoices()->count())->toBe(3)
        ->and($entry->type)->toBe(LedgerEntryType::InvoiceCreated)
        ->and($entry->payload)->toContain('Baju & Merchandise')
        ->and(app(Ledger::class)->firstBrokenSeq())->toBeNull();
});

test('regular user cannot create or view periods', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/periods', ['title' => 'x', 'amount' => 10000, 'due_date' => now()->toDateString()])->assertForbidden();
    $this->actingAs($user)->get('/periods')->assertForbidden();

    expect(BillingPeriod::count())->toBe(0);
});

test('period input is validated and nothing is written on failure', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post('/periods', ['title' => '', 'amount' => 5, 'due_date' => now()->subDay()->toDateString()])
        ->assertSessionHasErrors(['title', 'amount', 'due_date']);

    expect(BillingPeriod::count())->toBe(0)->and(LedgerEntry::count())->toBe(0);
});

test('the periods page lists paid progress', function () {
    $admin = User::factory()->admin()->create();
    $period = BillingPeriod::factory()->create(['title' => 'Kas minggu 8']);
    Invoice::factory()->paid()->create(['billing_period_id' => $period->id]);
    Invoice::factory()->create(['billing_period_id' => $period->id]);

    $this->actingAs($admin)->get('/periods')->assertOk()->assertSee('Kas minggu 8')->assertSee('1 / 2 lunas');
});

test('admin can search and filter periods by status badges', function () {
    $admin = User::factory()->admin()->create();

    // 1. Completed period
    $completedPeriod = BillingPeriod::factory()->create([
        'title' => 'Kas Wisuda Selesai',
        'due_date' => now()->addWeek(),
    ]);
    Invoice::factory()->paid()->create(['billing_period_id' => $completedPeriod->id]);

    // 2. Overdue period
    $overduePeriod = BillingPeriod::factory()->create([
        'title' => 'Kas Semester Lalu',
        'due_date' => now()->subWeeks(2),
    ]);
    Invoice::factory()->create(['billing_period_id' => $overduePeriod->id, 'status' => 'unpaid']);

    // 3. Active period
    $activePeriod = BillingPeriod::factory()->create([
        'title' => 'Kas Aktif Berjalan',
        'due_date' => now()->addDays(5),
    ]);
    Invoice::factory()->create(['billing_period_id' => $activePeriod->id, 'status' => 'unpaid']);

    // View all with badges
    $this->actingAs($admin)->get('/periods')
        ->assertOk()
        ->assertSee('Kas Wisuda Selesai')
        ->assertSee('Lunas Semua')
        ->assertSee('Kas Semester Lalu')
        ->assertSee('Lewat Tempo')
        ->assertSee('Kas Aktif Berjalan')
        ->assertSee('Berjalan');

    // Filter active only
    $this->actingAs($admin)->get('/periods?status=active')
        ->assertOk()
        ->assertSee('Kas Aktif Berjalan')
        ->assertDontSee('Kas Wisuda Selesai')
        ->assertDontSee('Kas Semester Lalu');

    // Filter overdue only
    $this->actingAs($admin)->get('/periods?status=overdue')
        ->assertOk()
        ->assertSee('Kas Semester Lalu')
        ->assertDontSee('Kas Wisuda Selesai')
        ->assertDontSee('Kas Aktif Berjalan');

    // Filter completed only
    $this->actingAs($admin)->get('/periods?status=completed')
        ->assertOk()
        ->assertSee('Kas Wisuda Selesai')
        ->assertDontSee('Kas Semester Lalu')
        ->assertDontSee('Kas Aktif Berjalan');

    // Search by title
    $this->actingAs($admin)->get('/periods?q=Wisuda')
        ->assertOk()
        ->assertSee('Kas Wisuda Selesai')
        ->assertDontSee('Kas Aktif Berjalan');
});

test('admin can create subsequent period reusing latest existing QRIS url without re-uploading file', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create();

    // First period has a QRIS url
    BillingPeriod::factory()->create([
        'title' => 'Kas Pekan 1',
        'qris_url' => 'https://example.test/dana-bisnis-qris.png',
    ]);

    // Admin creates second period without providing qris_image or qris_url
    $this->actingAs($admin)->post('/periods', [
        'title' => 'Kas Pekan 2',
        'amount' => 15000,
        'due_date' => now()->addWeek()->toDateString(),
    ])->assertRedirect();

    $newPeriod = BillingPeriod::where('title', 'Kas Pekan 2')->firstOrFail();
    expect($newPeriod->qris_url)->toBe('https://example.test/dana-bisnis-qris.png');
});

test('admin can create period selecting specific existing QRIS from dropdown', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create();

    BillingPeriod::factory()->create([
        'title' => 'Kas A',
        'qris_url' => 'https://example.test/qris-lama.png',
    ]);
    BillingPeriod::factory()->create([
        'title' => 'Kas B',
        'qris_url' => '00020101021126580016ID.CO.DANA.WWW5802ID6304ABCD',
    ]);

    $this->actingAs($admin)->post('/periods', [
        'title' => 'Kas Pekan Pilihan',
        'amount' => 20000,
        'due_date' => now()->addWeek()->toDateString(),
        'existing_qris' => '00020101021126580016ID.CO.DANA.WWW5802ID6304ABCD',
    ])->assertRedirect();

    $newPeriod = BillingPeriod::where('title', 'Kas Pekan Pilihan')->firstOrFail();
    expect($newPeriod->qris_url)->toBe('00020101021126580016ID.CO.DANA.WWW5802ID6304ABCD');
});

test('sidebar navigation uses sticky h-screen desktop layout', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('md:h-screen')
        ->assertSee('md:sticky');
});

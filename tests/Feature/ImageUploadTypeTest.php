<?php

use App\Enums\LedgerEntryType;
use App\Models\BillingPeriod;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\User;
use App\Services\Ledger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(fn () => Storage::fake('public'));

dataset('rejected uploads', [
    'webp image' => fn () => UploadedFile::fake()->image('foto.webp'),
    'pdf document' => fn () => UploadedFile::fake()->create('nota.pdf', 100, 'application/pdf'),
]);

test('expense receipts accept only png or jpg', function (UploadedFile $file) {
    $admin = User::factory()->admin()->create();
    app(Ledger::class)->append(LedgerEntryType::PaymentConfirmed, ['amount' => 100000]);

    $this->actingAs($admin)->post(route('expenses.store'), [
        'title' => 'Beli spidol',
        'amount' => 20000,
        'spent_at' => now()->toDateString(),
        'receipt' => $file,
    ])->assertSessionHasErrors('receipt');

    expect(Expense::count())->toBe(0);

    $this->actingAs($admin)->post(route('expenses.store'), [
        'title' => 'Beli spidol',
        'amount' => 20000,
        'spent_at' => now()->toDateString(),
        'receipt' => UploadedFile::fake()->image('nota.png'),
    ])->assertSessionHasNoErrors();
})->with('rejected uploads');

test('payment proofs accept only png or jpg', function (UploadedFile $file) {
    $member = User::factory()->create();
    $invoice = Invoice::factory()->create(['user_id' => $member->id]);

    $this->actingAs($member)
        ->post(route('payments.proof', $invoice), ['proof' => $file])
        ->assertSessionHasErrors('proof');
})->with('rejected uploads');

test('qris images accept only png or jpg', function (UploadedFile $file) {
    $admin = User::factory()->admin()->create();
    User::factory()->create();

    $this->actingAs($admin)->post('/periods', [
        'title' => 'Kas Pekan Tes',
        'amount' => 15000,
        'due_date' => now()->addWeek()->toDateString(),
        'qris_image' => $file,
    ])->assertSessionHasErrors('qris_image');

    expect(BillingPeriod::where('title', 'Kas Pekan Tes')->exists())->toBeFalse();
})->with('rejected uploads');

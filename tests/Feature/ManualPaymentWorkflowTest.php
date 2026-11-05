<?php

use App\Models\BillingPeriod;
use App\Models\Category;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('admin and owner can view payment proof image, but other users get 403', function () {
    Storage::fake();

    $owner = User::factory()->create();
    $admin = User::factory()->admin()->create();
    $other = User::factory()->create();

    $period = BillingPeriod::factory()->create();
    $invoice = Invoice::factory()->create(['user_id' => $owner->id, 'billing_period_id' => $period->id]);

    $file = UploadedFile::fake()->image('bukti.jpg');
    $path = $file->store('payment-proofs');

    $payment = Payment::create([
        'invoice_id' => $invoice->id,
        'order_id' => 'KAS-'.$invoice->id,
        'amount' => $invoice->amount,
        'status' => 'pending',
        'proof_path' => $path,
    ]);

    // Admin can view
    $this->actingAs($admin)
        ->get(route('payments.proof.show', $payment))
        ->assertOk();

    // Owner can view
    $this->actingAs($owner)
        ->get(route('payments.proof.show', $payment))
        ->assertOk();

    // Other user forbidden
    $this->actingAs($other)
        ->get(route('payments.proof.show', $payment))
        ->assertForbidden();
});

test('admin can reject payment proof with a note and file is removed', function () {
    Storage::fake();

    $owner = User::factory()->create();
    $admin = User::factory()->admin()->create();
    $period = BillingPeriod::factory()->create();
    $invoice = Invoice::factory()->create(['user_id' => $owner->id, 'billing_period_id' => $period->id]);

    $file = UploadedFile::fake()->image('bukti.jpg');
    $path = $file->store('payment-proofs');

    $payment = Payment::create([
        'invoice_id' => $invoice->id,
        'order_id' => 'KAS-'.$invoice->id,
        'amount' => $invoice->amount,
        'status' => 'pending',
        'proof_path' => $path,
    ]);

    Storage::assertExists($path);

    $this->actingAs($admin)
        ->post(route('payments.reject', $payment), [
            'rejection_note' => 'Foto bukti tidak terbaca / buram',
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    $payment->refresh();

    expect($payment->proof_path)->toBeNull()
        ->and($payment->rejection_note)->toBe('Foto bukti tidak terbaca / buram')
        ->and($payment->status)->toBe('pending');

    Storage::assertMissing($path);

    // Member sees rejection note on payment page
    $this->actingAs($owner)
        ->get(route('payments.create', $invoice))
        ->assertOk()
        ->assertSee('Bukti sebelumnya ditolak')
        ->assertSee('Foto bukti tidak terbaca / buram');

    // Member can resubmit new proof, clearing rejection note
    $newFile = UploadedFile::fake()->image('bukti-baru.png');
    $this->actingAs($owner)
        ->post(route('payments.proof', $invoice), ['proof' => $newFile])
        ->assertRedirect();

    $payment->refresh();
    expect($payment->proof_path)->not->toBeNull()
        ->and($payment->rejection_note)->toBeNull();
});

test('submitting proof for already paid invoice is redirected without storing file', function () {
    Storage::fake();

    $owner = User::factory()->create();
    $period = BillingPeriod::factory()->create();
    $invoice = Invoice::factory()->paid()->create(['user_id' => $owner->id, 'billing_period_id' => $period->id]);

    $file = UploadedFile::fake()->image('bukti.jpg');

    $this->actingAs($owner)
        ->post(route('payments.proof', $invoice), ['proof' => $file])
        ->assertRedirect()
        ->assertSessionHas('status', 'Tagihan ini sudah lunas.');

    expect(Storage::disk()->allFiles('payment-proofs'))->toBeEmpty();
});

test('admin can upload qris image file when creating billing period', function () {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();
    User::factory()->create();

    $image = UploadedFile::fake()->image('qris-kelas.png', 400, 400);

    $this->actingAs($admin)->post('/periods', [
        'title' => 'Kas Pekan 10',
        'amount' => 15000,
        'due_date' => now()->addWeek()->toDateString(),
        'qris_image' => $image,
    ])->assertRedirect();

    $period = BillingPeriod::where('title', 'Kas Pekan 10')->firstOrFail();

    expect($period->qris_url)->toContain('/storage/qris/');

    $storedPath = str_replace(asset('storage').'/', '', $period->qris_url);
    Storage::disk('public')->assertExists($storedPath);
});

test('admin can access review list with category information while regular user is forbidden', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['name' => 'Fadhil Rahman', 'student_no' => '07']);
    $other = User::factory()->create();

    $category = Category::create(['name' => 'Kepanitiaan Lomba']);
    $period = BillingPeriod::factory()->create(['title' => 'Pekan Olahraga', 'category_id' => $category->id]);
    $invoice = Invoice::factory()->create(['user_id' => $user->id, 'billing_period_id' => $period->id]);

    Payment::create([
        'invoice_id' => $invoice->id,
        'order_id' => 'KAS-REVIEW-01',
        'amount' => $invoice->amount,
        'status' => 'pending',
        'proof_path' => 'payment-proofs/dummy.jpg',
    ]);

    $this->actingAs($other)
        ->get(route('payments.review'))
        ->assertForbidden();

    $this->actingAs($admin)
        ->get(route('payments.review'))
        ->assertOk()
        ->assertSee('Fadhil Rahman')
        ->assertSee('Kepanitiaan Lomba')
        ->assertSee('Pekan Olahraga');
});

test('payment page displays download qris button, nominal copy helper, and clear static instructions', function () {
    $student = User::factory()->create();
    $period = BillingPeriod::factory()->create([
        'title' => 'Kas Pekan 3',
        'amount' => 15000,
        'qris_url' => 'https://example.test/qris-static.png',
    ]);
    $invoice = Invoice::factory()->create([
        'user_id' => $student->id,
        'billing_period_id' => $period->id,
        'amount' => 15000,
    ]);

    $this->actingAs($student)
        ->get(route('payments.create', $invoice))
        ->assertOk()
        ->assertSee('Unduh gambar QRIS')
        ->assertSee('Salin nominal angka')
        ->assertSee('Petunjuk Pembayaran QRIS Statis')
        ->assertSee('Scan dari Galeri')
        ->assertSee('tepat Rp15.000');
});

test('payment page dynamically generates nominal QRIS when period has static EMVCo payload', function () {
    $student = User::factory()->create();
    $staticPayload = '00020101021126580016ID.CO.DANA.WWW53033605802ID5911KAS KELAS 1A6007JAKARTA6304ABCD';

    $period = BillingPeriod::factory()->create([
        'title' => 'Kas Pekan Dinamis',
        'amount' => 25000,
        'qris_url' => $staticPayload,
    ]);
    $invoice = Invoice::factory()->create([
        'user_id' => $student->id,
        'billing_period_id' => $period->id,
        'amount' => 25000,
    ]);

    $response = $this->actingAs($student)
        ->get(route('payments.create', $invoice))
        ->assertOk()
        ->assertSee('QRIS dinamis (Nominal terkunci)')
        ->assertSee('Petunjuk Pembayaran QRIS Dinamis')
        ->assertSee('Nominal otomatis')
        ->assertDontSee('Salin nominal angka');

    $payment = Payment::where('invoice_id', $invoice->id)->firstOrFail();
    expect($payment->qr_content)->toContain('010212')
        ->and($payment->qr_content)->toContain('540525000')
        ->and($payment->qr_content)->not->toContain('010211');
});

test('admin can create period with raw qris payload string', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create();

    $payload = '00020101021126580016ID.CO.DANA.WWW5802ID6304ABCD';

    $this->actingAs($admin)->post('/periods', [
        'title' => 'Kas Dinamis DANA Bisnis',
        'amount' => 20000,
        'due_date' => now()->addWeek()->toDateString(),
        'qris_payload' => $payload,
    ])->assertRedirect();

    $period = BillingPeriod::where('title', 'Kas Dinamis DANA Bisnis')->firstOrFail();
    expect($period->qris_url)->toBe($payload);
});

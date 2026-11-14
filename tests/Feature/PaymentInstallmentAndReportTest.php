<?php

use App\Enums\LedgerEntryType;
use App\Models\BillingPeriod;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\User;
use App\Services\Ledger;
use App\Services\ManualPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('member can pay invoice in installments before due date until fully paid', function () {
    Storage::fake();

    $admin = User::factory()->admin()->create();
    $student = User::factory()->create(['name' => 'Rizky Pratama', 'student_no' => '11']);

    $staticPayload = '00020101021126580016ID.CO.DANA.WWW53033605802ID5911KAS KELAS 1A6007JAKARTA6304ABCD';
    $period = BillingPeriod::factory()->create([
        'title' => 'Kas Bulan November',
        'amount' => 20000,
        'due_date' => now()->addDays(5)->toDateString(),
        'qris_url' => $staticPayload,
    ]);

    $invoice = Invoice::factory()->create([
        'user_id' => $student->id,
        'billing_period_id' => $period->id,
        'amount' => 20000,
        'status' => 'unpaid',
    ]);

    // 1. Open payment page -> default pending payment is 20000
    $this->actingAs($student)
        ->get(route('payments.create', $invoice))
        ->assertOk()
        ->assertSee('Opsi Cicilan Uang Kas');

    // 2. Set first installment amount to Rp8.000
    $this->actingAs($student)
        ->post(route('payments.installment', $invoice), ['amount' => 8000])
        ->assertRedirect()
        ->assertSessionHas('status');

    $firstPayment = Payment::where('invoice_id', $invoice->id)->where('status', 'pending')->firstOrFail();
    expect($firstPayment->amount)->toBe(8000)
        ->and($firstPayment->qr_content)->toContain('54048000');

    // 3. Submit proof for first installment (Rp8.000)
    $this->actingAs($student)
        ->post(route('payments.proof', $invoice), [
            'proof' => UploadedFile::fake()->image('cicilan-1.jpg'),
        ])
        ->assertRedirect();

    // 4. Admin approves first installment -> invoice becomes 'partial', remaining is Rp12.000
    $this->actingAs($admin)
        ->post(route('payments.approve', $firstPayment))
        ->assertRedirect();

    $invoice->refresh();
    expect($invoice->status)->toBe('partial')
        ->and($invoice->isPartial())->toBeTrue()
        ->and($invoice->paidAmount())->toBe(8000)
        ->and($invoice->remainingAmount())->toBe(12000)
        ->and(app(Ledger::class)->balance())->toBe(8000);

    // 5. Member visits payment page again for second installment -> creates new pending payment for remaining Rp12.000
    $this->actingAs($student)
        ->get(route('payments.create', $invoice))
        ->assertOk()
        ->assertSee('Menyicil (40% terbayar)')
        ->assertSee('Rp12.000');

    $secondPayment = Payment::where('invoice_id', $invoice->id)->where('status', 'pending')->firstOrFail();
    expect($secondPayment->id)->not->toBe($firstPayment->id)
        ->and($secondPayment->order_id)->toBe('KAS-'.$invoice->id.'-2')
        ->and($secondPayment->amount)->toBe(12000);

    // 6. Submit proof and admin approves second installment -> invoice becomes 'paid'
    $this->actingAs($student)
        ->post(route('payments.proof', $invoice), [
            'proof' => UploadedFile::fake()->image('cicilan-2.jpg'),
        ])
        ->assertRedirect();

    $this->actingAs($admin)
        ->post(route('payments.approve', $secondPayment))
        ->assertRedirect();

    $invoice->refresh();
    expect($invoice->status)->toBe('paid')
        ->and($invoice->isPaid())->toBeTrue()
        ->and($invoice->paidAmount())->toBe(20000)
        ->and($invoice->remainingAmount())->toBe(0)
        ->and(app(Ledger::class)->balance())->toBe(20000)
        ->and(app(Ledger::class)->firstBrokenSeq())->toBeNull();
});

test('overdue invoice locks installment feature and requires full remaining amount settlement', function () {
    Storage::fake();

    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();

    $period = BillingPeriod::factory()->create([
        'title' => 'Kas Lewat Tenggat',
        'amount' => 30000,
        'due_date' => now()->subDays(4)->toDateString(),
        'qris_url' => 'https://example.test/qris.png',
    ]);

    $invoice = Invoice::factory()->partial()->create([
        'user_id' => $student->id,
        'billing_period_id' => $period->id,
        'amount' => 30000,
    ]);

    // Simulate Rp10.000 already paid before deadline
    Payment::create([
        'invoice_id' => $invoice->id,
        'order_id' => 'KAS-'.$invoice->id,
        'amount' => 10000,
        'status' => 'paid',
        'paid_at' => now()->subDays(6),
    ]);

    // Also simulate a stale pending installment of Rp5.000 created before deadline
    $stalePending = Payment::create([
        'invoice_id' => $invoice->id,
        'order_id' => 'KAS-'.$invoice->id.'-2',
        'amount' => 5000,
        'status' => 'pending',
    ]);

    expect($invoice->isOverdue())->toBeTrue()
        ->and($invoice->overdueDays())->toBe(4)
        ->and($invoice->canInstallment())->toBeFalse()
        ->and($invoice->remainingAmount())->toBe(20000);

    // 1. Visiting payment page shows overdue alert and forces pending amount to full remaining Rp20.000
    $this->actingAs($student)
        ->get(route('payments.create', $invoice))
        ->assertOk()
        ->assertSee('Lewat Tenggat Waktu (4 hari) — Fitur Menyicil Ditutup')
        ->assertDontSee('Opsi Cicilan Uang Kas');

    expect($stalePending->refresh()->amount)->toBe(20000);

    // 2. Trying to set partial installment after due date fails validation
    $this->actingAs($student)
        ->post(route('payments.installment', $invoice), ['amount' => 10000])
        ->assertSessionHasErrors('amount');

    // 3. Trying to submit proof with partial amount after due date fails validation
    $this->actingAs($student)
        ->post(route('payments.proof', $invoice), [
            'amount' => 10000,
            'proof' => UploadedFile::fake()->image('bukti-kurang.jpg'),
        ])
        ->assertSessionHasErrors('amount');

    // 4. Submitting proof for full remaining amount succeeds and records is_late in ledger
    $this->actingAs($student)
        ->post(route('payments.proof', $invoice), [
            'proof' => UploadedFile::fake()->image('bukti-lunas-telat.jpg'),
        ])
        ->assertRedirect();

    app(ManualPaymentService::class)->approve($stalePending->refresh(), $admin);

    $invoice->refresh();
    $entry = LedgerEntry::where('type', LedgerEntryType::PaymentConfirmed)
        ->where('ref_id', $stalePending->id)
        ->firstOrFail();
    $payload = json_decode($entry->payload, true);

    expect($invoice->isPaid())->toBeTrue()
        ->and($payload['is_late'])->toBeTrue()
        ->and($payload['amount'])->toBe(20000)
        ->and($payload['paid_total'])->toBe(30000)
        ->and($payload['remaining_amount'])->toBe(0);
});

test('reversing one installment of a paid invoice reverts invoice status to partial instead of unpaid', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();
    $period = BillingPeriod::factory()->create(['amount' => 10000]);
    $invoice = Invoice::factory()->create([
        'user_id' => $student->id,
        'billing_period_id' => $period->id,
        'amount' => 10000,
    ]);

    $p1 = Payment::create([
        'invoice_id' => $invoice->id,
        'order_id' => 'KAS-C1',
        'amount' => 4000,
        'status' => 'pending',
    ]);
    $p2 = Payment::create([
        'invoice_id' => $invoice->id,
        'order_id' => 'KAS-C2',
        'amount' => 6000,
        'status' => 'pending',
    ]);

    $service = app(ManualPaymentService::class);
    $service->approve($p1, $admin);
    $service->approve($p2, $admin);

    expect($invoice->refresh()->status)->toBe('paid');

    $secondEntry = LedgerEntry::where('type', LedgerEntryType::PaymentConfirmed)
        ->where('ref_id', $p2->id)
        ->firstOrFail();

    $this->actingAs($admin)
        ->post(route('ledger.reverse', $secondEntry), ['reason' => 'Salah nominal cicilan kedua'])
        ->assertRedirect();

    $invoice->refresh();
    expect($invoice->status)->toBe('partial')
        ->and($invoice->paidAmount())->toBe(4000)
        ->and($invoice->remainingAmount())->toBe(6000);
});

test('payment report page displays summary, filters by status/period/category, and exports CSV', function () {
    $admin = User::factory()->admin()->create();
    $s1 = User::factory()->create(['name' => 'Alya Putri', 'student_no' => '01']);
    $s2 = User::factory()->create(['name' => 'Bagas Saputra', 'student_no' => '02']);
    $s3 = User::factory()->create(['name' => 'Caca Marica', 'student_no' => '03']);

    $catKas = Category::create(['name' => 'Kas Rutin']);
    $catEvent = Category::create(['name' => 'Dana Makrab']);

    $activePeriod = BillingPeriod::factory()->create([
        'category_id' => $catKas->id,
        'title' => 'Kas Minggu 1',
        'amount' => 20000,
        'due_date' => now()->addDays(7)->toDateString(),
    ]);

    $overduePeriod = BillingPeriod::factory()->create([
        'category_id' => $catEvent->id,
        'title' => 'Iuran Makrab',
        'amount' => 50000,
        'due_date' => now()->subDays(5)->toDateString(),
    ]);

    // Alya: Paid Rp20.000
    $invPaid = Invoice::factory()->paid()->create([
        'user_id' => $s1->id,
        'billing_period_id' => $activePeriod->id,
        'amount' => 20000,
    ]);
    Payment::create([
        'invoice_id' => $invPaid->id,
        'order_id' => 'KAS-P1',
        'amount' => 20000,
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    // Bagas: Partial Rp8.000 of Rp20.000
    $invPartial = Invoice::factory()->partial()->create([
        'user_id' => $s2->id,
        'billing_period_id' => $activePeriod->id,
        'amount' => 20000,
    ]);
    Payment::create([
        'invoice_id' => $invPartial->id,
        'order_id' => 'KAS-P2',
        'amount' => 8000,
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    // Caca: Overdue Unpaid Rp50.000
    Invoice::factory()->create([
        'user_id' => $s3->id,
        'billing_period_id' => $overduePeriod->id,
        'amount' => 50000,
        'status' => 'unpaid',
    ]);

    // 1. View full report
    $this->actingAs($admin)
        ->get(route('reports.payments'))
        ->assertOk()
        ->assertSee('Laporan pembayaran uang kas')
        ->assertSee('Rp28.000') // total collected (20k + 8k)
        ->assertSee('Rp62.000') // total remaining (12k + 50k)
        ->assertSee('Alya Putri')
        ->assertSee('Bagas Saputra')
        ->assertSee('Caca Marica')
        ->assertSee('Menyicil (40%)')
        ->assertSee('Terlambat 5 hari · Wajib Lunas Penuh');

    // 2. Filter by status=partial
    $this->actingAs($admin)
        ->get(route('reports.payments', ['status' => 'partial']))
        ->assertOk()
        ->assertSee('Bagas Saputra')
        ->assertDontSee('Alya Putri')
        ->assertDontSee('Caca Marica');

    // 3. Filter by status=overdue
    $this->actingAs($admin)
        ->get(route('reports.payments', ['status' => 'overdue']))
        ->assertOk()
        ->assertSee('Caca Marica')
        ->assertDontSee('Alya Putri')
        ->assertDontSee('Bagas Saputra');

    // 4. Export CSV
    $csvResponse = $this->actingAs($admin)
        ->get(route('reports.payments.export'));

    $csvResponse->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    $csvContent = $csvResponse->streamedContent();
    expect($csvContent)
        ->toContain('Alya Putri')
        ->toContain('Bagas Saputra')
        ->toContain('Menyicil')
        ->toContain('Lewat Tenggat (5 hari) - Wajib Pelunasan Penuh');
});

test('indonesian school class cash report features BKU, student matrix, official signoff, and dedicated CSV exports', function () {
    $admin = User::factory()->admin()->create(['name' => 'Pak Guru Admin', 'student_no' => 'ADM001']);
    $s1 = User::factory()->create(['name' => 'Ahmad Dahlan', 'student_no' => '2024001']);
    $s2 = User::factory()->create(['name' => 'Dewi Sartika', 'student_no' => '2024002']);

    $period1 = BillingPeriod::factory()->create([
        'title' => 'Kas Minggu 1',
        'amount' => 10000,
        'due_date' => now()->addDays(3)->toDateString(),
    ]);

    $period2 = BillingPeriod::factory()->create([
        'title' => 'Kas Minggu 2',
        'amount' => 10000,
        'due_date' => now()->addDays(10)->toDateString(),
    ]);

    // Ahmad paid period 1 in full (Rp10.000)
    $invAhmad1 = Invoice::factory()->paid()->create([
        'user_id' => $s1->id,
        'billing_period_id' => $period1->id,
        'amount' => 10000,
    ]);
    $pAhmad1 = Payment::create([
        'invoice_id' => $invAhmad1->id,
        'order_id' => 'KAS-AHMAD-1',
        'amount' => 10000,
        'status' => 'pending',
    ]);
    app(ManualPaymentService::class)->approve($pAhmad1, $admin);

    // Dewi paid period 1 partially (Rp4.000)
    $invDewi1 = Invoice::factory()->partial()->create([
        'user_id' => $s2->id,
        'billing_period_id' => $period1->id,
        'amount' => 10000,
    ]);
    $pDewi1 = Payment::create([
        'invoice_id' => $invDewi1->id,
        'order_id' => 'KAS-DEWI-1',
        'amount' => 4000,
        'status' => 'pending',
    ]);
    app(ManualPaymentService::class)->approve($pDewi1, $admin);

    // An approved expense of Rp3.000 for class stationery
    $expense = Expense::create([
        'title' => 'Beli Spidol & Penghapus Papan Tulis',
        'amount' => 3000,
        'spent_at' => now()->toDateString(),
        'created_by' => $admin->id,
    ]);
    app(Ledger::class)->append(
        LedgerEntryType::ExpenseApproved,
        [
            'expense_id' => $expense->id,
            'title' => $expense->title,
            'amount' => $expense->amount,
            'spent_at' => $expense->spent_at->toDateString(),
        ],
        $admin,
        'expenses',
        $expense->id,
    );

    // 1. Check Matrix Tab
    $this->actingAs($admin)
        ->get(route('reports.payments', ['tab' => 'matrix']))
        ->assertOk()
        ->assertSee('Matriks Rekapitulasi Iuran Kas Siswa')
        ->assertSee('Buku Pembantu Kas')
        ->assertSee('Ahmad Dahlan')
        ->assertSee('Dewi Sartika')
        ->assertSee('2024001')
        ->assertSee('2024002')
        ->assertSee('Lembar Pengesahan Laporan Kas Kelas')
        ->assertSee('Wali Kelas 1A')
        ->assertSee('Ketua Kelas 1A')
        ->assertSee('Bendahara Kelas 1A');

    // 2. Check BKU Tab
    $this->actingAs($admin)
        ->get(route('reports.payments', ['tab' => 'bku']))
        ->assertOk()
        ->assertSee('Buku Kas Umum (BKU) Kas Kelas 1A')
        ->assertSee('Standar Akuntansi Kas')
        ->assertSee('Penerimaan iuran kas: Ahmad Dahlan')
        ->assertSee('Penerimaan iuran kas: Dewi Sartika')
        ->assertSee('Pengeluaran kas: Beli Spidol & Penghapus Papan Tulis')
        ->assertSee('Rp14.000') // Total Debit (10k + 4k)
        ->assertSee('Rp3.000')  // Total Credit
        ->assertSee('Rp11.000'); // Ending Balance (14k - 3k)

    // 3. Export BKU CSV
    $bkuExport = $this->actingAs($admin)
        ->get(route('reports.payments.export-bku'));
    $bkuExport->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    $bkuCsv = $bkuExport->streamedContent();
    expect($bkuCsv)
        ->toContain('BUKU KAS UMUM (BKU) KAS KELAS 1A')
        ->toContain('Ahmad Dahlan')
        ->toContain('Beli Spidol & Penghapus Papan Tulis')
        ->toContain('TOTAL PENERIMAAN (DEBET)')
        ->toContain('TOTAL PENGELUARAN (KREDIT)')
        ->toContain('SALDO KAS TERSEDIA');

    // 4. Export Matrix CSV
    $matrixExport = $this->actingAs($admin)
        ->get(route('reports.payments.export-matrix'));
    $matrixExport->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    $matrixCsv = $matrixExport->streamedContent();
    expect($matrixCsv)
        ->toContain('MATRIKS REKAPITULASI IURAN KAS SISWA KELAS 1A')
        ->toContain('Ahmad Dahlan')
        ->toContain('Dewi Sartika')
        ->toContain('2024001')
        ->toContain('2024002');
});

test('nim and student name have dedicated spacing container across member and period views', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create([
        'name' => 'Muhammad Fadhil Ramadhan',
        'student_no' => '202409001234', // Long 12-digit NIM
    ]);

    $period = BillingPeriod::factory()->create([
        'title' => 'Kas Semester Genap',
        'amount' => 25000,
    ]);

    Invoice::factory()->create([
        'user_id' => $student->id,
        'billing_period_id' => $period->id,
        'amount' => 25000,
        'status' => 'unpaid',
    ]);

    // Period detail view contains the min-w-16 non-colliding badge for student_no
    $this->actingAs($admin)
        ->get(route('periods.show', $period))
        ->assertOk()
        ->assertSee('202409001234')
        ->assertSee('Muhammad Fadhil Ramadhan')
        ->assertSee('min-w-16');

    // Member list view contains the min-w-16 non-colliding badge for student_no
    $this->actingAs($admin)
        ->get(route('members.index'))
        ->assertOk()
        ->assertSee('202409001234')
        ->assertSee('Muhammad Fadhil Ramadhan')
        ->assertSee('min-w-16');
});

test('admin is strictly excluded from billing, payment processing, and cash reports', function () {
    $admin = User::factory()->admin()->create(['name' => 'Bapak Wali Kelas Admin', 'student_no' => 'ADM999']);
    $student = User::factory()->create(['name' => 'Budi Santoso', 'student_no' => '2024010']);

    // 1. User payable check
    expect($admin->isAdmin())->toBeTrue()
        ->and($admin->isPayable())->toBeFalse()
        ->and($student->isPayable())->toBeTrue();

    // 2. Period creation only invoices payable members
    $period = BillingPeriod::factory()->create(['title' => 'Iuran Praktikum', 'amount' => 15000]);
    $payableMembers = User::payableMembers()->pluck('id');
    $period->invoices()->createMany(
        $payableMembers->map(fn (int $id) => ['user_id' => $id, 'amount' => $period->amount])->all()
    );

    expect($period->invoices()->count())->toBe(1)
        ->and($period->invoices()->where('user_id', $admin->id)->exists())->toBeFalse()
        ->and($period->invoices()->where('user_id', $student->id)->exists())->toBeTrue();

    // 3. Even if an invoice hypothetically exists for admin, payment routes block it with 403
    $adminInvoice = Invoice::factory()->create([
        'user_id' => $admin->id,
        'billing_period_id' => $period->id,
        'amount' => 15000,
        'status' => 'unpaid',
    ]);

    $this->actingAs($admin)
        ->get(route('payments.create', $adminInvoice))
        ->assertForbidden();

    $this->actingAs($admin)
        ->post(route('payments.installment', $adminInvoice), ['amount' => 5000])
        ->assertForbidden();

    $this->actingAs($admin)
        ->post(route('payments.proof', $adminInvoice), [
            'proof' => UploadedFile::fake()->image('bukti.jpg'),
        ])
        ->assertForbidden();

    // 4. In reports, admin is excluded from detail breakdown, matrix, and export
    $reportRes = $this->actingAs($admin)->get(route('reports.payments'));
    $reportRes->assertOk();
    $reportInvoices = $reportRes->viewData('invoices');
    expect($reportInvoices->pluck('user_id'))->not->toContain($admin->id);

    // Matrix view: admin has no row in student matrix
    $matrixRes = $this->actingAs($admin)->get(route('reports.payments', ['tab' => 'matrix']));
    $matrixRes->assertOk();
    $matrixData = $matrixRes->viewData('matrixData');
    $matrixUserIds = collect($matrixData['rows'])->pluck('user.id');
    expect($matrixUserIds)->not->toContain($admin->id)
        ->and($matrixUserIds)->toContain($student->id);

    // CSV exports: admin is excluded
    $csvStream = $this->actingAs($admin)->get(route('reports.payments.export'))->streamedContent();
    expect($csvStream)->not->toContain('Bapak Wali Kelas Admin')
        ->not->toContain('ADM999');

    $matrixCsvStream = $this->actingAs($admin)->get(route('reports.payments.export-matrix'))->streamedContent();
    expect($matrixCsvStream)->not->toContain('Bapak Wali Kelas Admin')
        ->not->toContain('ADM999');
});

test('staff bendahara can pay own cash and help pay installments on behalf of another student', function () {
    Storage::fake();

    $admin = User::factory()->admin()->create(['name' => 'Wali Kelas Admin']);
    $staff = User::factory()->staff()->create(['name' => 'Siti Bendahara', 'student_no' => 'BEN001']);
    $student = User::factory()->create(['name' => 'Doni Siswa', 'student_no' => '2024015']);
    $otherStudent = User::factory()->create(['name' => 'Eka Siswa', 'student_no' => '2024016']);

    $period = BillingPeriod::factory()->create([
        'title' => 'Kas Minggu 3',
        'amount' => 20000,
        'due_date' => now()->addDays(5)->toDateString(),
        'qris_url' => 'https://example.test/qris.png',
    ]);

    $staffInvoice = Invoice::factory()->create([
        'user_id' => $staff->id,
        'billing_period_id' => $period->id,
        'amount' => 20000,
        'status' => 'unpaid',
    ]);

    $studentInvoice = Invoice::factory()->create([
        'user_id' => $student->id,
        'billing_period_id' => $period->id,
        'amount' => 20000,
        'status' => 'unpaid',
    ]);

    // 1. Staff can pay their own cash invoice
    $this->actingAs($staff)
        ->get(route('payments.create', $staffInvoice))
        ->assertOk()
        ->assertDontSee('Mode Bendahara Kelas');

    $this->actingAs($staff)
        ->post(route('payments.proof', $staffInvoice), [
            'proof' => UploadedFile::fake()->image('bukti-staff.jpg'),
        ])
        ->assertRedirect()
        ->assertSessionHas('status', 'Bukti pembayaran dikirim. Menunggu verifikasi admin.');

    // 2. Regular student cannot pay another student invoice (403)
    $this->actingAs($otherStudent)
        ->get(route('payments.create', $studentInvoice))
        ->assertForbidden();

    $this->actingAs($otherStudent)
        ->post(route('payments.installment', $studentInvoice), ['amount' => 10000])
        ->assertForbidden();

    // 3. Staff (Bendahara) CAN open another student invoice and sees Mode Bendahara
    $this->actingAs($staff)
        ->get(route('payments.create', $studentInvoice))
        ->assertOk()
        ->assertSee('Bantu Pembayaran Kas Siswa (Mode Bendahara Kelas)')
        ->assertSee('Doni Siswa')
        ->assertSee('2024015');

    // 4. Staff adjusts installment nominal for student
    $this->actingAs($staff)
        ->post(route('payments.installment', $studentInvoice), ['amount' => 7000])
        ->assertRedirect()
        ->assertSessionHas('status');

    $pendingPayment = Payment::where('invoice_id', $studentInvoice->id)->where('status', 'pending')->firstOrFail();
    expect($pendingPayment->amount)->toBe(7000);

    // 5. Staff submits proof on behalf of student
    $this->actingAs($staff)
        ->post(route('payments.proof', $studentInvoice), [
            'proof' => UploadedFile::fake()->image('bukti-setor-tunai.jpg'),
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    $pendingPayment->refresh();
    expect($pendingPayment->gateway_response['paid_by_staff_name'])->toBe('Siti Bendahara')
        ->and($pendingPayment->gateway_response['paid_by_staff_id'])->toBe($staff->id);

    // 6. Admin sees the staff assistance indicator in Review view
    $this->actingAs($admin)
        ->get(route('payments.review'))
        ->assertOk()
        ->assertSee('Bendahara: Siti Bendahara')
        ->assertSee('Doni Siswa');

    // 7. Admin approves the payment -> ledger & BKU record the transaction and staff assistance
    app(ManualPaymentService::class)->approve($pendingPayment, $admin);

    $studentInvoice->refresh();
    expect($studentInvoice->status)->toBe('partial')
        ->and($studentInvoice->paidAmount())->toBe(7000);

    $entry = LedgerEntry::where('type', LedgerEntryType::PaymentConfirmed)
        ->where('ref_id', $pendingPayment->id)
        ->firstOrFail();
    $payload = json_decode($entry->payload, true);
    expect($payload['paid_by_staff_name'])->toBe('Siti Bendahara')
        ->and($payload['name'])->toBe('Doni Siswa');

    // 8. BKU view displays the staff attribution in description
    $this->actingAs($admin)
        ->get(route('reports.payments', ['tab' => 'bku']))
        ->assertOk()
        ->assertSee('Penerimaan iuran kas: Doni Siswa (2024015) — Kas Minggu 3 (via Bendahara: Siti Bendahara)');
});

test('report pdf renders for every tab and matrix/bku tabs paginate', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->count(30)->create();

    foreach (['detail', 'matrix', 'bku'] as $tab) {
        $response = $this->actingAs($admin)->get(route('reports.payments.pdf', ['tab' => $tab]));
        $response->assertOk();
        expect($response->headers->get('content-type'))->toContain('application/pdf');
    }

    $this->actingAs($admin)
        ->get(route('reports.payments', ['tab' => 'matrix']))
        ->assertOk()
        ->assertSee('1–25')
        ->assertSee('dari')
        ->assertSee('anggota');
});

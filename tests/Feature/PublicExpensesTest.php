<?php

use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->staff = User::factory()->staff()->create(['name' => 'Siti Bendahara']);
    $this->expense = Expense::create([
        'title' => 'Spidol whiteboard',
        'amount' => 25000,
        'spent_at' => '2026-10-02',
        'notes' => 'Transfer ke rekening 1234567890',
        'receipt_path' => 'receipts/nota.jpg',
        'created_by' => $this->staff->id,
    ]);
});

test('guests see expenses without notes, recorder name, receipt link or form', function () {
    $this->get(route('expenses.index'))
        ->assertOk()
        ->assertSee('Spidol whiteboard')
        ->assertSee('Rp25.000')
        ->assertSee('Pengurus kelas')
        ->assertSee('Nota tersedia untuk anggota')
        ->assertDontSee('Siti Bendahara')
        ->assertDontSee('1234567890')
        ->assertDontSee(route('expenses.receipt', $this->expense))
        ->assertDontSee('Catat pengeluaran');
});

test('guests cannot find expenses by searching hidden notes', function () {
    $this->get(route('expenses.index', ['q' => 'rekening']))
        ->assertOk()
        ->assertDontSee('Spidol whiteboard');
});

test('guests opening a receipt are sent to login', function () {
    $this->get(route('expenses.receipt', $this->expense))->assertRedirect(route('login'));
});

test('logged-in staff still see the form, notes, recorder and receipt link', function () {
    $this->actingAs(User::factory()->staff()->create())
        ->get(route('expenses.index'))
        ->assertOk()
        ->assertSee('Catat pengeluaran')
        ->assertSee('Siti Bendahara')
        ->assertSee('1234567890')
        ->assertSee(route('expenses.receipt', $this->expense))
        ->assertDontSee('Nota tersedia untuk anggota');
});

test('public expenses api lists newest first without personal data', function () {
    $older = Expense::create([
        'title' => 'Fotokopi modul',
        'amount' => 10000,
        'spent_at' => '2026-09-30',
        'created_by' => $this->staff->id,
    ]);

    $this->getJson(route('api.v1.public.expenses'))
        ->assertOk()
        ->assertJsonPath('data.0', [
            'id' => $this->expense->id,
            'title' => 'Spidol whiteboard',
            'amount' => 25000,
            'spent_at' => '2026-10-02',
            'has_receipt' => true,
            'created_at' => $this->expense->created_at->toIso8601String(),
        ])
        ->assertJsonPath('data.1.id', $older->id)
        ->assertJsonPath('data.1.has_receipt', false)
        ->assertDontSee('Siti Bendahara')
        ->assertDontSee('1234567890')
        ->assertDontSee('nota.jpg');
});

test('public expenses api filters by month and validates input', function () {
    Expense::create([
        'title' => 'Fotokopi modul',
        'amount' => 10000,
        'spent_at' => '2026-09-30',
        'created_by' => $this->staff->id,
    ]);

    $this->getJson(route('api.v1.public.expenses', ['month' => '2026-09']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Fotokopi modul');

    $this->getJson(route('api.v1.public.expenses', ['month' => '2026-13']))->assertUnprocessable();
    $this->getJson(route('api.v1.public.expenses', ['per_page' => 500]))->assertUnprocessable();
});

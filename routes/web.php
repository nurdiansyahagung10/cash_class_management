<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ManualPaymentController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\PaymentReportController;
use App\Http\Controllers\PeriodController;
use Illuminate\Support\Facades\Route;

// Guests see the public cash summary; members get their own dashboard on the same URL.

Route::get('/', DashboardController::class)->name('dashboard');
Route::get('/ledger', LedgerController::class)->name('ledger');
Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/password', [PasswordController::class, 'update'])->name('password.update');

    Route::middleware('password.changed')->group(function () {
        Route::middleware('staff_admin')->group(function () {
            Route::get('/reports/payments', [PaymentReportController::class, 'index'])->name('reports.payments');
            Route::get('/reports/payments/export', [PaymentReportController::class, 'export'])->name('reports.payments.export');
            Route::get('/reports/payments/export-bku', [PaymentReportController::class, 'exportBku'])->name('reports.payments.export-bku');
            Route::get('/reports/payments/export-matrix', [PaymentReportController::class, 'exportMatrix'])->name('reports.payments.export-matrix');
            Route::get('/payments/review', [ManualPaymentController::class, 'index'])->name('payments.review');
            Route::post('/payments/{payment}/approve', [ManualPaymentController::class, 'approve'])->name('payments.approve');
            Route::post('/payments/{payment}/reject', [ManualPaymentController::class, 'reject'])->name('payments.reject');
            Route::get('/periods', [PeriodController::class, 'index'])->name('periods.index');
            Route::post('/periods', [PeriodController::class, 'store'])->name('periods.store');
            Route::get('/periods/{period}', [PeriodController::class, 'show'])->name('periods.show');
            Route::get('/reports/payments', [PaymentReportController::class, 'index'])->name('reports.payments');
            Route::get('/reports/payments/export', [PaymentReportController::class, 'export'])->name('reports.payments.export');
            Route::get('/reports/payments/export-bku', [PaymentReportController::class, 'exportBku'])->name('reports.payments.export-bku');
            Route::get('/reports/payments/export-matrix', [PaymentReportController::class, 'exportMatrix'])->name('reports.payments.export-matrix');
            Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
            Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        });

        Route::middleware('admin')->group(function () {

            Route::get('/members', [MemberController::class, 'index'])->name('members.index');
            Route::post('/members', [MemberController::class, 'store'])->name('members.store');
            Route::post('/members/import', [MemberController::class, 'import'])->name('members.import');
            Route::get('/members/template', [MemberController::class, 'template'])->name('members.template');
            Route::post('/members/{pending}/approve', [MemberController::class, 'approve'])->name('members.approve');
            Route::delete('/members/{pending}', [MemberController::class, 'reject'])->name('members.reject');
            Route::post('/members/{user}/toggle-staff', [MemberController::class, 'toggleStaff'])->name('members.toggle-staff');
            Route::post('/members/{user}/toggle-status', [MemberController::class, 'toggleStatus'])->name('members.toggle-status');
            Route::post('/members/bulk-action', [MemberController::class, 'bulkAction'])->name('members.bulk-action');

        });

        Route::get('/ledger/export', [LedgerController::class, 'export'])->name('ledger.export');
        Route::post('/ledger/verify', [LedgerController::class, 'verify'])->name('ledger.verify');
        Route::post('/ledger/{entry}/reverse', [LedgerController::class, 'reverse'])->name('ledger.reverse');

        Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
        Route::get('/expenses/{expense}/receipt', [ExpenseController::class, 'receipt'])->name('expenses.receipt');

        Route::get('/invoices/{invoice}/pay', [ManualPaymentController::class, 'create'])->name('payments.create');
        Route::post('/invoices/{invoice}/installment', [ManualPaymentController::class, 'updateInstallment'])->name('payments.installment');
        Route::post('/invoices/{invoice}/payment-proof', [ManualPaymentController::class, 'submitProof'])->name('payments.proof');
        Route::get('/payments/{payment}/proof', [ManualPaymentController::class, 'proof'])->name('payments.proof.show');

    });
});

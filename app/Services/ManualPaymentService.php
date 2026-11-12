<?php

namespace App\Services;

use App\Enums\LedgerEntryType;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ManualPaymentService
{
    public function __construct(private Ledger $ledger) {}

    public function approve(Payment $payment, User $reviewer): Payment
    {
        return DB::transaction(function () use ($payment, $reviewer): Payment {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            if ($payment->isPaid()) {
                return $payment;
            }

            $payment->update([
                'status' => 'paid',
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'paid_at' => now(),
            ]);

            $invoice = $payment->invoice()->with(['user', 'period.category'])->firstOrFail();
            $invoice->syncStatusFromPayments();

            $categoryName = $payment->category?->name ?? $invoice->period?->category?->name ?? 'Kas Kelas';
            $isLate = $invoice->period !== null && $invoice->period->due_date->endOfDay()->isPast();

            $payload = [
                'amount' => $payment->amount,
                'order_id' => $payment->order_id,
                'invoice_id' => $payment->invoice_id,
                'user_id' => $invoice->user_id,
                'name' => $invoice->user->name,
                'student_no' => $invoice->user->student_no,
                'category' => $categoryName,
                'period' => $invoice->period?->title,
                'period_title' => $invoice->period?->title,
                'invoice_status' => $invoice->status,
                'paid_total' => $invoice->paidAmount(),
                'remaining_amount' => $invoice->remainingAmount(),
                'is_late' => $isLate,
            ];

            if (!empty($payment->gateway_response['paid_by_staff_name'])) {
                $payload['paid_by_staff_id'] = $payment->gateway_response['paid_by_staff_id'] ?? null;
                $payload['paid_by_staff_name'] = $payment->gateway_response['paid_by_staff_name'];
            }

            $this->ledger->append(
                LedgerEntryType::PaymentConfirmed,
                $payload,
                $reviewer,
                'payments',
                $payment->id,
            );

            return $payment;
        });
    }
}

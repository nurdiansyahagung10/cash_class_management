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
            $payment->invoice()->update(['status' => 'paid']);
            $categoryName = $payment->category?->name ?? $payment->invoice?->period?->category?->name ?? 'Kas Kelas';

            $this->ledger->append(
                LedgerEntryType::PaymentConfirmed,
                [
                    'amount' => $payment->amount,
                    'order_id' => $payment->order_id,
                    'invoice_id' => $payment->invoice_id,
                    'category' => $categoryName,
                    'period' => $payment->invoice?->period?->title,
                ],
                $reviewer,
                'payments',
                $payment->id,
            );

            return $payment;
        });
    }
}

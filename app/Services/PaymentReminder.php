<?php

namespace App\Services;

use App\Models\BillingPeriod;
use App\Models\Invoice;
use Illuminate\Support\Collection;

/**
 * WhatsApp reminder texts for unpaid invoices. There is no WhatsApp provider: staff copy or open these and send them by hand.
 */
class PaymentReminder
{
    /**
     * One message for the class group listing every member who has not paid in full.
     *
     * @param  Collection<int, Invoice>  $invoices  Unpaid and partial invoices of the period.
     */
    public function groupMessage(BillingPeriod $period, Collection $invoices): string
    {
        $members = $invoices->values()
            ->map(fn (Invoice $invoice, int $index): string => ($index + 1).". {$invoice->user->name} (sisa {$this->rupiah($invoice->remainingAmount())})")
            ->implode("\n");

        return "Halo teman-teman, mengingatkan tagihan kas kelas {$period->title} dengan jatuh tempo {$this->dueDate($period)}.\n\n"
            ."Yang belum lunas:\n{$members}\n\n"
            .'Mohon segera dibayar lewat aplikasi kas kelas ya: '.route('dashboard')."\n"
            .'Terima kasih!';
    }

    /**
     * Personal message with the remaining amount and the member's own pay link; past the due date it asks for full payment.
     */
    public function personalMessage(Invoice $invoice): string
    {
        $period = $invoice->period;
        $remaining = $this->rupiah($invoice->remainingAmount());

        $body = $invoice->isOverdue()
            ? "tagihan kas kelas {$period->title} sudah melewati jatuh tempo ({$this->dueDate($period)}). Mohon segera melunasi sisa tagihan penuh sebesar {$remaining}"
            : "mengingatkan tagihan kas kelas {$period->title} sebesar {$remaining} yang jatuh tempo pada {$this->dueDate($period)}. Mohon segera bayar/cicil";

        return "Halo {$invoice->user->name}, {$body} via QRIS kelas di tautan berikut ya:\n"
            .route('payments.create', $invoice)."\n\n"
            .'Terima kasih!';
    }

    /**
     * wa.me link to the member's own chat, or null when the member has no phone number.
     */
    public function whatsAppUrl(Invoice $invoice): ?string
    {
        $phone = preg_replace('/\D+/', '', (string) $invoice->user->phone);

        if ($phone === '') {
            return null;
        }

        if (str_starts_with($phone, '0')) {
            $phone = '62'.substr($phone, 1);
        }

        return "https://wa.me/{$phone}?text=".rawurlencode($this->personalMessage($invoice));
    }

    private function rupiah(int $amount): string
    {
        return 'Rp'.number_format($amount, 0, ',', '.');
    }

    private function dueDate(BillingPeriod $period): string
    {
        return $period->due_date->locale('id')->translatedFormat('j F Y');
    }
}

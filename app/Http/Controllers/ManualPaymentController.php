<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\ManualPaymentService;
use App\Services\QrisService;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use QrisDinamis\Qris;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Zxing\QrReader;

class ManualPaymentController extends Controller
{
    public function create(Invoice $invoice, Request $request, QrisService $qrisService): View|RedirectResponse
    {
        abort_if($invoice->user->isAdmin(), 403, 'Admin dikecualikan dari pembayaran.');
        $isOwner = $invoice->user_id === $request->user()->id;
        $isStaff = $request->user()->isStaff();
        abort_unless($isOwner || $isStaff, 403, 'Hanya pemilik tagihan atau bendahara (staff) yang dapat mengakses halaman pembayaran ini.');
        if ($invoice->isPaid()) {
            return back()->with('status', 'Tagihan ini sudah lunas.');
        }

        $invoice->load(['user', 'period.category', 'payments' => fn ($q) => $q->latest('id')]);
        $remainingAmount = $invoice->remainingAmount();
        $paidAmount = $invoice->paidAmount();
        $paidPayments = $invoice->payments->where('status', 'paid')->values();

        $pendingPayment = $invoice->payments()->where('status', 'pending')->latest('id')->first();

        $targetAmount = $pendingPayment ? min((int) $pendingPayment->amount, $remainingAmount) : $remainingAmount;
        if ($targetAmount <= 0 || $invoice->isOverdue()) {
            $targetAmount = $remainingAmount;
        }

        $uri = $this->resolveQrContent($invoice->period->qris_url, $targetAmount, $qrisService);

        if (!$pendingPayment) {
            $existingCount = $invoice->payments()->count();
            $orderId = $existingCount === 0 ? 'KAS-'.$invoice->id : 'KAS-'.$invoice->id.'-'.($existingCount + 1);

            $payment = $invoice->payments()->create([
                'order_id' => $orderId,
                'amount' => $targetAmount,
                'qr_content' => $uri,
                'category_id' => $invoice->period->category_id,
            ]);
        } else {
            $payment = $pendingPayment;
            $updates = [];

            if ((int) $payment->amount !== $targetAmount) {
                $updates['amount'] = $targetAmount;
            }
            if ($payment->qr_content !== $uri && $uri !== null) {
                $updates['qr_content'] = $uri;
            }
            if (!$payment->category_id && $invoice->period->category_id) {
                $updates['category_id'] = $invoice->period->category_id;
            }
            if ($updates !== []) {
                $payment->update($updates);
            }
        }

        $isAssisting = $isStaff && ! $isOwner;

        return view('payments.create', compact('invoice', 'payment', 'remainingAmount', 'paidAmount', 'paidPayments', 'isAssisting'));
    }

    public function updateInstallment(Invoice $invoice, Request $request, QrisService $qrisService): RedirectResponse
    {
        abort_if($invoice->user->isAdmin(), 403, 'Admin dikecualikan dari pembayaran.');
        $isOwner = $invoice->user_id === $request->user()->id;
        $isStaff = $request->user()->isStaff();
        abort_unless($isOwner || $isStaff, 403, 'Hanya pemilik tagihan atau bendahara (staff) yang dapat mengatur nominal cicilan.');

        if ($invoice->isPaid()) {
            return back()->with('status', 'Tagihan ini sudah lunas.');
        }

        $invoice->load(['period', 'user']);
        $remainingAmount = $invoice->remainingAmount();
        $requestedAmount = (int) $request->input('amount', $remainingAmount);

        if ($invoice->isOverdue() && $requestedAmount < $remainingAmount) {
            return back()->withErrors([
                'amount' => 'Masa tenggat sudah lewat. Fitur menyicil ditutup, wajib melunasi sisa tagihan penuh sebesar Rp'.number_format($remainingAmount, 0, ',', '.').'.',
            ])->withInput();
        }

        $minInstallment = min(1000, $remainingAmount);
        $validated = $request->validate([
            'amount' => ['required', 'integer', "min:{$minInstallment}", "max:{$remainingAmount}"],
        ]);

        $targetAmount = (int) $validated['amount'];
        $uri = $this->resolveQrContent($invoice->period->qris_url, $targetAmount, $qrisService);

        $payment = $invoice->payments()->where('status', 'pending')->latest('id')->first();
        if (!$payment) {
            $existingCount = $invoice->payments()->count();
            $orderId = $existingCount === 0 ? 'KAS-'.$invoice->id : 'KAS-'.$invoice->id.'-'.($existingCount + 1);
            $invoice->payments()->create([
                'order_id' => $orderId,
                'amount' => $targetAmount,
                'qr_content' => $uri,
                'category_id' => $invoice->period->category_id,
            ]);
        } else {
            $payment->update([
                'amount' => $targetAmount,
                'qr_content' => $uri ?? $payment->qr_content,
            ]);
        }

        $label = $targetAmount < $remainingAmount
            ? 'Nominal cicilan diatur ke Rp'.number_format($targetAmount, 0, ',', '.').'.'
            : 'Nominal diatur ke pelunasan penuh Rp'.number_format($targetAmount, 0, ',', '.').'.';

        if ($isStaff && ! $isOwner) {
            $label = "[Mode Bendahara] {$label} untuk siswa {$invoice->user->name}.";
        }

        return back()->with('status', $label);
    }

    public function submitProof(Invoice $invoice, Request $request, QrisService $qrisService): RedirectResponse
    {
        abort_if($invoice->user->isAdmin(), 403, 'Admin dikecualikan dari pembayaran.');
        $isOwner = $invoice->user_id === $request->user()->id;
        $isStaff = $request->user()->isStaff();
        abort_unless($isOwner || $isStaff, 403, 'Hanya pemilik tagihan atau bendahara (staff) yang dapat mengirimkan bukti pembayaran.');

        if ($invoice->isPaid()) {
            return back()->with('status', 'Tagihan ini sudah lunas.');
        }

        $invoice->load(['period', 'user']);
        $remainingAmount = $invoice->remainingAmount();

        if ($request->filled('amount')) {
            $requestedAmount = (int) $request->input('amount');
            if ($invoice->isOverdue() && $requestedAmount < $remainingAmount) {
                return back()->withErrors([
                    'amount' => 'Masa tenggat sudah lewat. Fitur menyicil ditutup, wajib melunasi sisa tagihan penuh sebesar Rp'.number_format($remainingAmount, 0, ',', '.').'.',
                ])->withInput();
            }
        }

        $minInstallment = min(1000, $remainingAmount);
        $validated = $request->validate([
            'proof' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'amount' => ['nullable', 'integer', "min:{$minInstallment}", "max:{$remainingAmount}"],
        ]);

        $payment = $invoice->payments()->where('status', 'pending')->latest('id')->first();
        $targetAmount = isset($validated['amount'])
            ? (int) $validated['amount']
            : ($payment ? min((int) $payment->amount, $remainingAmount) : $remainingAmount);

        if ($invoice->isOverdue()) {
            $targetAmount = $remainingAmount;
        }

        $gatewayData = $payment?->gateway_response ?? [];
        if ($isStaff && ! $isOwner) {
            $gatewayData['paid_by_staff_id'] = $request->user()->id;
            $gatewayData['paid_by_staff_name'] = $request->user()->name;
        }

        if (!$payment) {
            $existingCount = $invoice->payments()->count();
            $orderId = $existingCount === 0 ? 'KAS-'.$invoice->id : 'KAS-'.$invoice->id.'-'.($existingCount + 1);
            $payment = $invoice->payments()->create([
                'order_id' => $orderId,
                'amount' => $targetAmount,
                'qr_content' => $this->resolveQrContent($invoice->period->qris_url, $targetAmount, $qrisService),
                'category_id' => $invoice->period->category_id,
                'proof_path' => $validated['proof']->store('payment-proofs'),
                'rejection_note' => null,
                'gateway_response' => $gatewayData !== [] ? $gatewayData : null,
            ]);
        } else {
            $payment->update([
                'amount' => $targetAmount,
                'proof_path' => $validated['proof']->store('payment-proofs'),
                'rejection_note' => null,
                'gateway_response' => $gatewayData !== [] ? $gatewayData : $payment->gateway_response,
            ]);
        }

        $msg = ($isStaff && ! $isOwner)
            ? "Bukti pembayaran atas nama {$invoice->user->name} berhasil dikirim oleh bendahara ({$request->user()->name}). Menunggu verifikasi admin."
            : 'Bukti pembayaran dikirim. Menunggu verifikasi admin.';

        return back()->with('status', $msg);
    }

    private function resolveQrContent(?string $qrContent, int $amount, QrisService $qrisService): ?string
    {
        if ($qrContent === null || $qrContent === '') {
            return null;
        }

        $localPath = implode('/', array_slice(explode('/', $qrContent), 3));
        if ($localPath !== '' && is_file($localPath)) {
            $qr = Qris::convert((new QrReader($localPath))->text(), $amount);
            $writer = new PngWriter;
            $qrCode = new QrCode(
                data: $qr,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::Low,
                size: 300,
                margin: 10,
                roundBlockSizeMode: RoundBlockSizeMode::Margin,
            );

            return $writer->write($qrCode)->getDataUri();
        }

        return $qrisService->isQrisPayload($qrContent)
            ? $qrisService->toDynamic($qrContent, $amount)
            : $qrContent;
    }

    public function proof(Payment $payment, Request $request): StreamedResponse|BinaryFileResponse
    {
        $isOwner = $payment->invoice->user_id === $request->user()->id;
        $isStaff = $request->user()->isStaff();
        $isAdmin = $request->user()->isAdmin();

        abort_unless($isAdmin || $isOwner || $isStaff, 403);
        abort_unless($payment->proof_path && Storage::exists($payment->proof_path), 404);

        return Storage::response($payment->proof_path);
    }

    public function index(Request $request): View
    {
        abort_unless($request->user()->isAdmin(), 403);

        return view('payments.review', [
            'payments' => Payment::with(['invoice.user', 'invoice.period.category', 'category'])
                ->where('status', 'pending')
                ->whereNotNull('proof_path')
                ->latest()
                ->get(),
        ]);
    }

    public function approve(Payment $payment, Request $request, ManualPaymentService $service): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $service->approve($payment, $request->user());

        return back()->with('status', 'Pembayaran diverifikasi dan dicatat ke ledger.');
    }

    public function reject(Payment $payment, Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        if ($payment->isPaid()) {
            return back()->with('error', 'Pembayaran yang sudah lunas tidak dapat ditolak.');
        }

        $validated = $request->validate([
            'rejection_note' => ['nullable', 'string', 'max:255'],
        ]);

        if ($payment->proof_path && Storage::exists($payment->proof_path)) {
            Storage::delete($payment->proof_path);
        }

        $payment->update([
            'proof_path' => null,
            'rejection_note' => $validated['rejection_note'] ?? 'Bukti pembayaran ditolak admin. Silakan unggah bukti yang benar.',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('status', 'Bukti pembayaran ditolak. Anggota dapat mengunggah bukti baru.');
    }
}

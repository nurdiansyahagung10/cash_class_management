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
        abort_unless($invoice->user_id === $request->user()->id, 403);
        if ($invoice->isPaid()) {
            return back()->with('status', 'Tagihan ini sudah lunas.');
        }

        $qrContent = $invoice->period->qris_url;
        $qr = Qris::convert((new QrReader(implode('/', array_slice(explode('/', $qrContent), 3))))->text(), $invoice->amount);


        $writer = new PngWriter;

        $qrCode = new QrCode(
            data: $qr,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Low,
            size: 300,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        );
        $result = $writer->write($qrCode);
        $uri = $result->getDataUri();

        $payment = $invoice->payments()->where('status', 'pending')->latest('id')->firstOrCreate(
            [],
            [
                'order_id' => 'KAS-'.$invoice->id,
                'amount' => $invoice->amount,
                'qr_content' => $uri,
                'category_id' => $invoice->period->category_id,
            ],
        );

        if ($payment->qr_content !== $uri && $uri !== null) {
            $payment->update(['qr_content' => $uri]);
        }

        if (! $payment->category_id && $invoice->period->category_id) {
            $payment->update(['category_id' => $invoice->period->category_id]);
        }

        return view('payments.create', compact('invoice', 'payment'));
    }

    public function submitProof(Invoice $invoice, Request $request): RedirectResponse
    {
        abort_unless($invoice->user_id === $request->user()->id, 403);

        if ($invoice->isPaid()) {
            return back()->with('status', 'Tagihan ini sudah lunas.');
        }

        $validated = $request->validate(['proof' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120']]);
        $payment = $invoice->payments()->where('status', 'pending')->latest('id')->firstOrFail();
        $payment->update([
            'proof_path' => $validated['proof']->store('payment-proofs'),
            'rejection_note' => null,
        ]);

        return back()->with('status', 'Bukti pembayaran dikirim. Menunggu verifikasi admin.');
    }

    public function proof(Payment $payment, Request $request): StreamedResponse|BinaryFileResponse
    {
        abort_unless($request->user()->isAdmin() || $payment->invoice->user_id === $request->user()->id, 403);
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

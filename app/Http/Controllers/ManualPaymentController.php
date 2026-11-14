<?php

namespace App\Http\Controllers;

use App\Models\BillingPeriod;
use App\Models\Category;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Services\ManualPaymentService;
use App\Services\QrisService;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use QrisDinamis\Qris;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Zxing\QrReader;

class ManualPaymentController extends Controller
{
    private const COMBINED_LOCK_MESSAGE = 'Tagihan ini termasuk pembayaran sekaligus yang sedang menunggu verifikasi admin.';

    public function create(Invoice $invoice, Request $request, QrisService $qrisService): View|RedirectResponse
    {
        abort_if($invoice->user->isAdmin(), 403, 'Admin dikecualikan dari pembayaran.');
        $isOwner = $invoice->user_id === $request->user()->id;
        $isStaff = $request->user()->isStaff();
        abort_unless($isOwner || $isStaff, 403, 'Hanya pemilik tagihan atau bendahara (staff) yang dapat mengakses halaman pembayaran ini.');
        if ($invoice->isPaid()) {
            return back()->with('status', 'Tagihan ini sudah lunas.');
        }

        if ($this->isInCombinedPayment($invoice)) {
            return redirect()->route('dashboard')->with('status', self::COMBINED_LOCK_MESSAGE);
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

        if (! $pendingPayment) {
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
            if (! $payment->category_id && $invoice->period->category_id) {
                $updates['category_id'] = $invoice->period->category_id;
            }
            if ($updates !== []) {
                $payment->update($updates);
            }
        }

        $isAssisting = $isStaff && ! $isOwner;

        $combinableCount = $this->payableInvoices($invoice->user)->count();

        return view('payments.create', compact('invoice', 'payment', 'remainingAmount', 'paidAmount', 'paidPayments', 'isAssisting', 'combinableCount'));
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

        if ($this->isInCombinedPayment($invoice)) {
            return back()->with('status', self::COMBINED_LOCK_MESSAGE);
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
        if (! $payment) {
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

        if ($this->isInCombinedPayment($invoice)) {
            return back()->with('status', self::COMBINED_LOCK_MESSAGE);
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

        if (! $payment) {
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

    /**
     * Pick several of one member's unpaid invoices and settle them with a single transfer and proof.
     */
    public function bulk(Invoice $invoice, Request $request, QrisService $qrisService): View
    {
        $this->authorizeCombined($invoice, $request);

        $invoices = $this->payableInvoices($invoice->user);
        $ids = collect($request->query('ids', [$invoice->id]))->map(fn ($id) => (int) $id)->all();
        $selected = $invoices->whereIn('id', $ids)->values();
        $total = (int) $selected->sum(fn (Invoice $item) => $item->remainingAmount());
        $qrisUrl = $selected->map(fn (Invoice $item) => $item->period->qris_url)->filter()->last();

        return view('payments.bulk', [
            'anchor' => $invoice,
            'member' => $invoice->user,
            'invoices' => $invoices,
            'selectedIds' => $selected->pluck('id')->all(),
            'total' => $total,
            'qrContent' => $total > 0 && $qrisUrl ? $this->resolveQrContent($qrisUrl, $total, $qrisService) : null,
            'isAssisting' => $this->isAssisting($invoice, $request),
        ]);
    }

    /**
     * One proof for many invoices: each gets its own payment row (so the ledger keeps one entry per invoice) sharing a batch key and the proof file.
     */
    public function submitBulk(Invoice $invoice, Request $request): RedirectResponse
    {
        $this->authorizeCombined($invoice, $request);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:60'],
            'ids.*' => ['integer'],
            'proof' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $payable = $this->payableInvoices($invoice->user)->keyBy('id');
        $ids = collect($validated['ids'])->map(fn ($id) => (int) $id)->unique()->values();

        if ($ids->contains(fn (int $id) => ! $payable->has($id))) {
            return back()->withErrors(['ids' => 'Sebagian tagihan sudah lunas atau sedang menunggu verifikasi. Muat ulang halaman lalu pilih lagi.']);
        }

        $isAssisting = $this->isAssisting($invoice, $request);
        $batchKey = (string) Str::uuid();
        $proofPath = $validated['proof']->store('payment-proofs');
        $total = 0;

        DB::transaction(function () use ($ids, $payable, $isAssisting, $request, $batchKey, $proofPath, &$total) {
            foreach ($ids as $id) {
                $item = $payable[$id];
                $amount = $item->remainingAmount();
                $total += $amount;

                $attributes = [
                    'amount' => $amount,
                    'category_id' => $item->period->category_id,
                    'proof_path' => $proofPath,
                    'batch_key' => $batchKey,
                    'rejection_note' => null,
                    'gateway_response' => $isAssisting
                        ? ['paid_by_staff_id' => $request->user()->id, 'paid_by_staff_name' => $request->user()->name]
                        : null,
                ];

                $pending = $item->payments()->where('status', 'pending')->whereNull('proof_path')->latest('id')->first();
                if ($pending) {
                    $pending->update($attributes);

                    continue;
                }

                $existingCount = $item->payments()->count();
                $item->payments()->create([
                    ...$attributes,
                    'order_id' => $existingCount === 0 ? 'KAS-'.$item->id : 'KAS-'.$item->id.'-'.($existingCount + 1),
                ]);
            }
        });

        return redirect()->route('dashboard')->with('status', "Bukti pembayaran sekaligus untuk {$ids->count()} tagihan (Rp".number_format($total, 0, ',', '.').') dikirim. Menunggu verifikasi admin.');
    }

    /**
     * Unpaid invoices that can join a combined payment, earliest deadline first. Invoices whose proof is already awaiting review are left out.
     *
     * @return Collection<int, Invoice>
     */
    private function payableInvoices(User $member): Collection
    {
        return Invoice::query()
            ->where('user_id', $member->id)
            ->whereIn('status', ['unpaid', 'partial'])
            ->whereDoesntHave('payments', fn ($q) => $q->where('status', 'pending')->whereNotNull('proof_path'))
            ->with(['period.category', 'payments'])
            ->get()
            ->sortBy(fn (Invoice $item) => $item->period->due_date->getTimestamp())
            ->values();
    }

    private function authorizeCombined(Invoice $invoice, Request $request): void
    {
        abort_if($invoice->user->isAdmin(), 403, 'Admin dikecualikan dari pembayaran.');
        abort_unless($invoice->user_id === $request->user()->id || $request->user()->isStaff(), 403, 'Hanya pemilik tagihan atau bendahara (staff) yang dapat membayar sekaligus.');
    }

    private function isAssisting(Invoice $invoice, Request $request): bool
    {
        return $request->user()->isStaff() && $invoice->user_id !== $request->user()->id;
    }

    private function isInCombinedPayment(Invoice $invoice): bool
    {
        return $invoice->payments()->where('status', 'pending')->whereNotNull('batch_key')->exists();
    }

    /**
     * The pending payments that were sent with this one proof (just itself for a normal payment).
     *
     * @return Collection<int, Payment>
     */
    private function batchOf(Payment $payment): Collection
    {
        return $payment->batch_key
            ? Payment::where('batch_key', $payment->batch_key)->where('status', 'pending')->get()
            : collect([$payment]);
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
        $tab = $request->query('tab') === 'approved' ? 'approved' : 'pending';
        $periodId = $request->integer('period_id') ?: null;
        $categoryId = $request->integer('category_id') ?: null;
        $search = trim((string) $request->query('q'));

        $filtered = fn (string $status) => Payment::query()
            ->where('status', $status)
            ->when($status === 'pending', fn ($q) => $q->whereNotNull('proof_path')->where(
                fn ($w) => $w->whereNull('batch_key')->orWhereIn('id', Payment::query()->selectRaw('MIN(id)')->where('status', 'pending')->whereNotNull('batch_key')->groupBy('batch_key'))
            ))
            ->when($periodId, fn ($q) => $q->whereHas('invoice', fn ($i) => $i->where('billing_period_id', $periodId)))
            ->when($categoryId, fn ($q) => $q->where(
                fn ($w) => $w->where('category_id', $categoryId)
                    ->orWhereHas('invoice.period', fn ($p) => $p->where('category_id', $categoryId))
            ))
            ->when($search !== '', fn ($q) => $q->whereHas('invoice.user', fn ($u) => $u->where(
                fn ($w) => $w->where('name', 'like', "%{$search}%")->orWhere('student_no', 'like', "%{$search}%")
            )));

        $payments = $filtered($tab === 'approved' ? 'paid' : 'pending')
            ->with(['invoice.user', 'invoice.period.category', 'category', 'reviewer'])
            ->when($tab === 'approved', fn ($q) => $q->orderByDesc('reviewed_at')->orderByDesc('id'), fn ($q) => $q->latest())
            ->paginate(8)
            ->withQueryString();

        $batchKeys = $payments->pluck('batch_key')->filter()->unique()->values();

        return view('payments.review', [
            'payments' => $payments,
            'batches' => $batchKeys->isEmpty() ? collect() : Payment::with('invoice.period')->whereIn('batch_key', $batchKeys)->get()->groupBy('batch_key'),
            'tab' => $tab,
            'pendingCount' => $filtered('pending')->count(),
            'approvedCount' => $filtered('paid')->count(),
            'periods' => BillingPeriod::orderByDesc('id')->get(['id', 'title']),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'selectedPeriodId' => $periodId,
            'selectedCategoryId' => $categoryId,
            'search' => $search,
        ]);
    }

    public function approve(Payment $payment, Request $request, ManualPaymentService $service): RedirectResponse
    {

        abort_if($payment->needsAdminReview() && ! $request->user()->isAdmin(), 403, 'Pembayaran staf hanya dapat diverifikasi oleh admin.');

        $batch = $this->batchOf($payment);
        DB::transaction(function () use ($batch, $service, $request) {
            foreach ($batch as $row) {
                $service->approve($row, $request->user());
            }
        });

        return back()->with('status', $batch->count() > 1
            ? "{$batch->count()} pembayaran sekaligus diverifikasi dan dicatat ke ledger."
            : 'Pembayaran diverifikasi dan dicatat ke ledger.');
    }

    public function reject(Payment $payment, Request $request): RedirectResponse
    {

        abort_if($payment->needsAdminReview() && ! $request->user()->isAdmin(), 403, 'Pembayaran staf hanya dapat diproses oleh admin.');

        if ($payment->isPaid()) {
            return back()->with('error', 'Pembayaran yang sudah lunas tidak dapat ditolak.');
        }

        $validated = $request->validate([
            'rejection_note' => ['nullable', 'string', 'max:255'],
        ]);

        $proofPath = $payment->proof_path;
        $batch = $this->batchOf($payment);

        DB::transaction(function () use ($batch, $validated, $request) {
            foreach ($batch as $row) {
                $row->update([
                    'proof_path' => null,
                    'batch_key' => null,
                    'rejection_note' => $validated['rejection_note'] ?? 'Bukti pembayaran ditolak admin. Silakan unggah bukti yang benar.',
                    'reviewed_by' => $request->user()->id,
                    'reviewed_at' => now(),
                ]);
            }
        });

        if ($proofPath && Storage::exists($proofPath)) {
            Storage::delete($proofPath);
        }

        return back()->with('status', $batch->count() > 1
            ? 'Bukti pembayaran sekaligus ditolak untuk '.$batch->count().' tagihan. Anggota dapat mengunggah bukti baru.'
            : 'Bukti pembayaran ditolak. Anggota dapat mengunggah bukti baru.');
    }
}

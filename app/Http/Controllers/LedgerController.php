<?php

namespace App\Http\Controllers;

use App\Enums\LedgerEntryType;
use App\Models\Expense;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Services\Ledger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LedgerController extends Controller
{
    public function __invoke(Request $request, Ledger $ledger): View
    {
        $search = trim((string) $request->query('q'));
        $isGuest = $request->user() === null;

        $entries = LedgerEntry::query()
            ->with('actor')
            ->when($search !== '', fn($query) => $query->where(
                // Guests may not search payloads: matching a name would reveal who paid.
                fn($q) => $q->when(!$isGuest, fn($q) => $q->orWhere('payload', 'like', "%{$search}%"))
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhere('hash', 'like', "{$search}%")
            ))
            ->orderByDesc('seq')
            ->paginate(20)
            ->withQueryString();

        $reversedSeqs = LedgerEntry::where('type', LedgerEntryType::Reversal)
            ->where('ref_table', 'ledger_entries')
            ->pluck('ref_id')
            ->all();

        return view('ledger', [
            'entries' => $entries,
            'search' => $search,
            'isGuest' => $isGuest,
            'total' => LedgerEntry::count(),
            'brokenSeq' => $ledger->firstBrokenSeq(),
            'reversedSeqs' => $reversedSeqs,
        ]);
    }

    public function verify(Ledger $ledger): RedirectResponse
    {
        $start = microtime(true);
        $brokenSeq = $ledger->firstBrokenSeq();
        $elapsedMs = round((microtime(true) - $start) * 1000, 2);
        $total = LedgerEntry::count();

        if ($brokenSeq === null) {
            return back()->with('status', "Audit selesai ({$elapsedMs}ms): Seluruh {$total} entri SHA256 valid. Rantai ledger utuh.");
        }

        return back()->with('error', "Peringatan integritas: Rantai ledger terputus pada entri #{$brokenSeq}!");
    }

    public function reverse(LedgerEntry $entry, Request $request, Ledger $ledger): RedirectResponse
    {
        

        $allowedTypes = [LedgerEntryType::PaymentConfirmed, LedgerEntryType::ExpenseApproved];
        if (!in_array($entry->type, $allowedTypes, true)) {
            return back()->with('error', 'Hanya entri konfirmasi pembayaran dan pengeluaran yang dapat dibatalkan.');
        }

        $alreadyReversed = LedgerEntry::where('type', LedgerEntryType::Reversal)
            ->where('ref_table', 'ledger_entries')
            ->where('ref_id', $entry->seq)
            ->exists();

        if ($alreadyReversed) {
            return back()->with('error', "Entri #{$entry->seq} sudah pernah dibatalkan sebelumnya.");
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $payload = json_decode($entry->payload, true) ?? [];
        $amount = (int) ($payload['amount'] ?? 0);

        DB::transaction(function () use ($entry, $amount, $validated, $request, $ledger) {
            if ($entry->type === LedgerEntryType::PaymentConfirmed) {
                if ($entry->ref_table === 'payments' && $entry->ref_id) {
                    $payment = Payment::with('invoice')->find($entry->ref_id);
                    if ($payment) {
                        $payment->update(['status' => 'reversed']);
                        $payment->invoice?->syncStatusFromPayments();
                    }
                }
            } elseif ($entry->type === LedgerEntryType::ExpenseApproved) {
                if ($entry->ref_table === 'expenses' && $entry->ref_id) {
                    $expense = Expense::find($entry->ref_id);
                    if ($expense) {
                        if ($expense->receipt_path && Storage::exists($expense->receipt_path)) {
                            Storage::delete($expense->receipt_path);
                        }
                        $expense->delete();
                    }
                }
            }

            $ledger->append(
                LedgerEntryType::Reversal,
                [
                    'reversed_seq' => $entry->seq,
                    'type' => $entry->type->value,
                    'amount' => $amount,
                    'reason' => $validated['reason'] ?? 'Koreksi kesalahan pencatatan transaksi',
                ],
                $request->user(),
                'ledger_entries',
                $entry->seq,
            );
        });

        return back()->with('status', "Entri #{$entry->seq} berhasil dibatalkan dengan entri REVERSAL baru di ledger.");
    }

    public function export(): StreamedResponse
    {
        $filename = 'ledger-kas-kelas-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Seq', 'Tipe', 'Kategori', 'Payload / Detail', 'Aktor', 'Tanggal', 'Hash Sebelumnya', 'Hash']);

            LedgerEntry::query()
                ->with('actor')
                ->orderBy('seq')
                ->lazyById(500, 'seq')
                ->each(function (LedgerEntry $entry) use ($handle) {
                    $payload = json_decode($entry->payload, true) ?? [];
                    $category = $payload['category'] ?? '-';

                    fputcsv($handle, [
                        $entry->seq,
                        $entry->type->value,
                        $category,
                        $entry->payload,
                        $entry->actor?->name ?? 'Sistem',
                        $entry->created_at->format('Y-m-d H:i:s'),
                        $entry->prev_hash ?? '-',
                        $entry->hash,
                    ]);
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}

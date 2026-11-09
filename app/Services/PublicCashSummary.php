<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\LedgerAnchor;
use App\Models\LedgerEntry;
use App\Models\User;

/**
 * Class-wide cash figures that are safe to show anyone: aggregates only, no member data.
 */
class PublicCashSummary
{
    public function __construct(private Ledger $ledger) {}

    /**
     * @return array{balance: int, brokenSeq: int|null, anchorsIntact: bool, latestAnchor: LedgerAnchor|null, totalEntries: int, activeMembers: int, paidRate: int}
     */
    public function toArray(): array
    {
        $invoiceStats = Invoice::whereHas('user', fn ($uq) => $uq->where('role', '!=', UserRole::Admin))
            ->selectRaw('COUNT(*) as invoice_count, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as paid_count', ['paid'])
            ->first();

        return [
            'balance' => $this->ledger->balance(),
            'brokenSeq' => $this->ledger->firstBrokenSeq(),
            'anchorsIntact' => $this->ledger->firstMismatchedAnchor() === null,
            'latestAnchor' => LedgerAnchor::query()->latest('anchored_on')->first(),
            'totalEntries' => LedgerEntry::count(),
            'activeMembers' => User::payableMembers()->count(),
            'paidRate' => (int) $invoiceStats->invoice_count === 0 ? 0 : (int) round($invoiceStats->paid_count * 100 / $invoiceStats->invoice_count),
        ];
    }
}

<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\BillingPeriod;
use App\Models\Invoice;
use App\Models\LedgerAnchor;
use App\Models\LedgerEntry;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Class-wide cash figures that are safe to show anyone: aggregates only, no member data.
 */
class PublicCashSummary
{
    public function __construct(private Ledger $ledger) {}

    /**
     * @return array{balance: int, brokenSeq: int|null, anchorsIntact: bool, latestAnchor: LedgerAnchor|null, totalEntries: int, activeMembers: int, paidRate: int, overall: array<string, int>, periodSummaries: Collection<int, array<string, mixed>>}
     */
    public function toArray(): array
    {
        Invoice::repairStatuses();

        $periods = BillingPeriod::with([
            'category',
            'invoices' => fn ($q) => $q->whereHas('user', fn ($uq) => $uq->where('role', '!=', UserRole::Admin)),
            'invoices.payments',
        ])->latest('id')->get();

        $summaries = $periods->map(function (BillingPeriod $period): array {
            $invoices = $period->invoices;
            $target = (int) $invoices->sum('amount');
            $collected = (int) $invoices->sum(fn (Invoice $inv) => $inv->paidAmount());
            $paid = $invoices->where('status', 'paid')->count();

            return [
                'title' => $period->title,
                'category' => $period->category?->name,
                'due_date' => $period->due_date,
                'target' => $target,
                'collected' => $collected,
                'remaining' => max(0, $target - $collected),
                'rate' => $target > 0 ? (int) round($collected * 100 / $target) : 0,
                'total_count' => $invoices->count(),
                'paid_count' => $paid,
                'partial_count' => $invoices->where('status', 'partial')->count(),
                'unpaid_count' => $invoices->where('status', 'unpaid')->count(),
                'is_incoming' => $period->isIncoming(),
                'is_overdue' => $period->isPastDue() && $paid < $invoices->count(),
            ];
        });

        $target = (int) $summaries->sum('target');
        $collected = (int) $summaries->sum('collected');
        $invoiceCount = (int) $summaries->sum('total_count');
        $paidCount = (int) $summaries->sum('paid_count');

        return [
            'balance' => $this->ledger->balance(),
            'brokenSeq' => $this->ledger->firstBrokenSeq(),
            'anchorsIntact' => $this->ledger->firstMismatchedAnchor() === null,
            'latestAnchor' => LedgerAnchor::query()->latest('anchored_on')->first(),
            'totalEntries' => LedgerEntry::count(),
            'activeMembers' => User::payableMembers()->count(),
            'paidRate' => $invoiceCount === 0 ? 0 : (int) round($paidCount * 100 / $invoiceCount),
            'overall' => [
                'target' => $target,
                'collected' => $collected,
                'remaining' => max(0, $target - $collected),
                'rate' => $target > 0 ? (int) round($collected * 100 / $target) : 0,
                'invoice_count' => $invoiceCount,
                'paid_count' => $paidCount,
                'partial_count' => (int) $summaries->sum('partial_count'),
                'unpaid_count' => (int) $summaries->sum('unpaid_count'),
                'period_count' => $summaries->count(),
            ],
            'periodSummaries' => $summaries->take(6)->values(),
        ];
    }
}

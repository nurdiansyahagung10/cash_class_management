<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicLedgerEntryResource;
use App\Models\LedgerAnchor;
use App\Models\LedgerEntry;
use App\Services\PublicCashSummary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Read-only public API (e.g. for syncing to Notion). Exposes only what the public pages show.
 */
class PublicCashController extends Controller
{
    public function summary(PublicCashSummary $summary): JsonResponse
    {
        $stats = $summary->toArray();

        return response()->json(['data' => [
            'class' => 'Kas Kelas 1A',
            'balance' => $stats['balance'],
            'balance_formatted' => 'Rp'.number_format($stats['balance'], 0, ',', '.'),
            'active_members' => $stats['activeMembers'],
            'paid_rate_percent' => $stats['paidRate'],
            'ledger' => [
                'total_entries' => $stats['totalEntries'],
                'intact' => $stats['brokenSeq'] === null,
                'broken_seq' => $stats['brokenSeq'],
            ],
            'latest_anchor' => $stats['latestAnchor'] === null ? null : $this->anchorData($stats['latestAnchor']),
            'anchors_intact' => $stats['anchorsIntact'],
            'generated_at' => now()->toIso8601String(),
        ]]);
    }

    /**
     * Daily anchors, newest first, so anyone can keep copies and compare them with the ledger later.
     */
    public function anchors(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $anchors = LedgerAnchor::query()
            ->orderByDesc('anchored_on')
            ->paginate($validated['per_page'] ?? 50)
            ->withQueryString()
            ->through($this->anchorData(...));

        return JsonResource::collection($anchors);
    }

    /**
     * @return array{date: string, seq: int, hash: string}
     */
    private function anchorData(LedgerAnchor $anchor): array
    {
        return [
            'date' => $anchor->anchored_on->toDateString(),
            'seq' => $anchor->seq,
            'hash' => $anchor->hash,
        ];
    }

    /**
     * Entries in ascending seq order; pass `since_seq` to fetch only new entries on each sync.
     */
    public function ledger(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'since_seq' => ['nullable', 'integer', 'min:0'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $entries = LedgerEntry::query()
            ->when(isset($validated['since_seq']), fn ($query) => $query->where('seq', '>', $validated['since_seq']))
            ->orderBy('seq')
            ->paginate($validated['per_page'] ?? 50)
            ->withQueryString();

        return PublicLedgerEntryResource::collection($entries);
    }
}

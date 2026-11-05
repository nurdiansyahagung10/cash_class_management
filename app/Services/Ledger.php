<?php

namespace App\Services;

use App\Enums\LedgerEntryType;
use App\Models\LedgerEntry;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Ledger
{
    /**
     * Append an entry chained to the previous one.
     *
     * @param  array<string, mixed>  $payload
     */
    public function append(
        LedgerEntryType $type,
        array $payload,
        ?User $actor = null,
        ?string $refTable = null,
        ?int $refId = null,
    ): LedgerEntry {
        return DB::transaction(function () use ($type, $payload, $actor, $refTable, $refId) {
            $last = LedgerEntry::query()->orderByDesc('seq')->lockForUpdate()->first();

            $entry = new LedgerEntry([
                'seq' => ($last?->seq ?? 0) + 1,
                'type' => $type,
                'ref_table' => $refTable,
                'ref_id' => $refId,
                'payload' => $this->canonicalJson($payload),
                'actor_id' => $actor?->id,
                'created_at' => now()->format('Y-m-d H:i:s'),
                'prev_hash' => $last?->hash,
            ]);
            $entry->hash = $this->hashOf($entry);
            $entry->save();

            return $entry;
        });
    }

    /**
     * Cash balance: confirmed payments minus approved expenses, always derived from the ledger.
     */
    public function balance(): int
    {
        $types = [
            LedgerEntryType::PaymentConfirmed->value,
            LedgerEntryType::ExpenseApproved->value,
            LedgerEntryType::Reversal->value,
        ];
        $balance = 0;

        foreach (LedgerEntry::query()->whereIn('type', $types)->lazyById(500, 'seq') as $entry) {
            $payload = json_decode($entry->payload, true) ?? [];
            $amount = (int) ($payload['amount'] ?? 0);

            if ($entry->type === LedgerEntryType::PaymentConfirmed) {
                $balance += $amount;
            } elseif ($entry->type === LedgerEntryType::ExpenseApproved) {
                $balance -= $amount;
            } elseif ($entry->type === LedgerEntryType::Reversal) {
                $reversedType = $payload['type'] ?? null;
                if ($reversedType === LedgerEntryType::PaymentConfirmed->value) {
                    $balance -= $amount;
                } elseif ($reversedType === LedgerEntryType::ExpenseApproved->value) {
                    $balance += $amount;
                }
            }
        }

        return $balance;
    }

    /**
     * Calendar grid for one month with daily cash in/out taken from the ledger.
     * Reversals count as the opposite flow, so day net always matches balance().
     *
     * @return array{
     *     month: Carbon,
     *     weeks: array<int, array<int, array{date: string, day: int, isToday: bool, in: int, out: int}|null>>,
     *     totalIn: int,
     *     totalOut: int
     * }
     */
    public function calendarData(Carbon $month): array
    {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();
        $days = [];

        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $days[$d->format('Y-m-d')] = ['in' => 0, 'out' => 0];
        }

        $types = [
            LedgerEntryType::PaymentConfirmed->value,
            LedgerEntryType::ExpenseApproved->value,
            LedgerEntryType::Reversal->value,
        ];

        $entries = LedgerEntry::query()
            ->whereIn('type', $types)
            ->whereBetween('created_at', [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')])
            ->orderBy('seq')
            ->lazyById(500, 'seq');

        foreach ($entries as $entry) {
            $payload = json_decode($entry->payload, true) ?? [];
            $amount = (int) ($payload['amount'] ?? 0);
            $key = $entry->created_at->format('Y-m-d');
            $reversed = $payload['type'] ?? null;

            $isIn = $entry->type === LedgerEntryType::PaymentConfirmed
                || ($entry->type === LedgerEntryType::Reversal && $reversed === LedgerEntryType::ExpenseApproved->value);

            $days[$key][$isIn ? 'in' : 'out'] += $amount;
        }

        $today = now()->format('Y-m-d');
        $cells = array_fill(0, $start->dayOfWeekIso - 1, null);

        foreach ($days as $date => $flow) {
            $cells[] = ['date' => $date, 'day' => (int) substr($date, 8), 'isToday' => $date === $today] + $flow;
        }

        $cells = array_pad($cells, (int) (ceil(count($cells) / 7) * 7), null);

        return [
            'month' => $start,
            'weeks' => array_chunk($cells, 7),
            'totalIn' => array_sum(array_column($days, 'in')),
            'totalOut' => array_sum(array_column($days, 'out')),
        ];
    }

    /**
     * Recompute the whole chain. shortcut: callers recompute on each page view, cache the last result when the ledger grows.
     *
     * @return int|null seq of the first broken entry, or null when the chain is intact
     */
    public function firstBrokenSeq(): ?int
    {
        $previousHash = null;
        $expectedSeq = 1;

        foreach (LedgerEntry::query()->lazyById(500, 'seq') as $entry) {
            if ($entry->seq !== $expectedSeq || $entry->prev_hash !== $previousHash || $entry->hash !== $this->hashOf($entry)) {
                return $entry->seq;
            }

            $previousHash = $entry->hash;
            $expectedSeq++;
        }

        return null;
    }

    private function hashOf(LedgerEntry $entry): string
    {
        return hash('sha256', json_encode([
            $entry->seq,
            $entry->type->value,
            $entry->payload,
            (string) $entry->created_at,
            $entry->prev_hash,
        ]));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function canonicalJson(array $payload): string
    {
        $sort = function (array $data) use (&$sort): array {
            ksort($data);

            return array_map(fn ($value) => is_array($value) ? $sort($value) : $value, $data);
        };

        return json_encode($sort($payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

<?php

namespace App\Http\Resources;

use App\Models\LedgerEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ledger entry for the public API: personal payload keys and actor names are left out.
 *
 * @mixin LedgerEntry
 */
class PublicLedgerEntryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $payload = json_decode($this->publicPayload(), true);

        return [
            'seq' => $this->seq,
            'type' => $this->type->value,
            'amount' => isset($payload['amount']) ? (int) $payload['amount'] : null,
            'category' => $payload['category'] ?? null,
            'payload' => $payload,
            'recorded_by' => $this->actor_id ? 'Pengurus kelas' : 'Sistem',
            'created_at' => $this->created_at->toIso8601String(),
            'prev_hash' => $this->prev_hash,
            'hash' => $this->hash,
        ];
    }
}

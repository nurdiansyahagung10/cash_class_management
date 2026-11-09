<?php

namespace App\Models;

use App\Enums\LedgerEntryType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;
use LogicException;

/**
 * Append-only row of the hash-chain ledger. Create entries only through App\Services\Ledger.
 *
 * `payload` is kept as the exact canonical JSON string that was hashed.
 */
class LedgerEntry extends Model
{
    public const UPDATED_AT = null;

    /**
     * Payload keys that identify a person; hidden from guests on the public ledger.
     */
    public const PERSONAL_KEYS = ['name', 'student_no', 'paid_by_staff_id', 'paid_by_staff_name'];

    protected $primaryKey = 'seq';

    protected $guarded = [];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Ledger entries cannot be updated.'));
        static::deleting(fn () => throw new LogicException('Ledger entries cannot be deleted.'));
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * Payload JSON without personal keys, for viewers who are not logged in.
     */
    public function publicPayload(): string
    {
        $payload = Arr::except(json_decode($this->payload, true) ?? [], self::PERSONAL_KEYS);

        return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['type' => LedgerEntryType::class];
    }
}

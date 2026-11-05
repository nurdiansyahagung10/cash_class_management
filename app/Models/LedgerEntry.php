<?php

namespace App\Models;

use App\Enums\LedgerEntryType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Append-only row of the hash-chain ledger. Create entries only through App\Services\Ledger.
 *
 * `payload` is kept as the exact canonical JSON string that was hashed.
 */
class LedgerEntry extends Model
{
    public const UPDATED_AT = null;

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
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['type' => LedgerEntryType::class];
    }
}

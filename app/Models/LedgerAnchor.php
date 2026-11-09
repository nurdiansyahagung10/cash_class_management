<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * Daily published copy of the ledger head hash. Append-only; create anchors only through App\Services\Ledger.
 */
class LedgerAnchor extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Ledger anchors cannot be updated.'));
        static::deleting(fn () => throw new LogicException('Ledger anchors cannot be deleted.'));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['anchored_on' => 'date'];
    }
}

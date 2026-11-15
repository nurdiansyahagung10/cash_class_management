<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * The weekly-cash category: its periods are named "Kas Minggu ke N" and always run weekly.
     */
    public static function isCashName(?string $name): bool
    {
        return in_array(mb_strtolower(trim((string) $name)), ['kas', 'uang kas'], true);
    }

    public function isCash(): bool
    {
        return self::isCashName($this->name);
    }

    public function billingPeriods(): HasMany
    {
        return $this->hasMany(BillingPeriod::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}

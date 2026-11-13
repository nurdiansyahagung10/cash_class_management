<?php

namespace App\Models;

use Database\Factories\BillingPeriodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['category_id', 'title', 'amount', 'due_date', 'qris_url', 'created_by'])]
class BillingPeriod extends Model
{
    /** @use HasFactory<BillingPeriodFactory> */
    use HasFactory;

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Cancellable only within a day of creation and while no payment (of any status) exists.
     */
    public function canBeCancelled(): bool
    {
        return $this->created_at->addDay()->isFuture()
            && ! Payment::whereIn('invoice_id', $this->invoices()->select('id'))->exists();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['due_date' => 'date'];
    }
}

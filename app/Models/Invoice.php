<?php

namespace App\Models;

use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * `status` is a cache of the ledger (unpaid/partial/paid); PAYMENT_CONFIRMED in the ledger is the truth.
 */
#[Fillable(['user_id', 'billing_period_id', 'amount', 'status'])]
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isPartial(): bool
    {
        return $this->status === 'partial';
    }

    public function paidAmount(): int
    {
        if ($this->isPaid()) {
            return (int) $this->amount;
        }

        if ($this->relationLoaded('payments')) {
            return min((int) $this->amount, (int) $this->payments->where('status', 'paid')->sum('amount'));
        }

        return min((int) $this->amount, (int) $this->payments()->where('status', 'paid')->sum('amount'));
    }

    public function remainingAmount(): int
    {
        return max(0, (int) $this->amount - $this->paidAmount());
    }

    public function isOverdue(): bool
    {
        return ! $this->isPaid()
            && $this->period !== null
            && $this->period->isPastDue();
    }

    public function overdueDays(): int
    {
        if (! $this->isOverdue()) {
            return 0;
        }

        return max(1, (int) $this->period->dueAt()->startOfDay()->diffInDays(now(BillingPeriod::TIMEZONE)->startOfDay()));
    }

    public function canInstallment(): bool
    {
        return ! $this->isPaid() && ! $this->isOverdue();
    }

    public function syncStatusFromPayments(): void
    {
        $paid = (int) $this->payments()->where('status', 'paid')->sum('amount');

        $status = match (true) {
            $paid >= (int) $this->amount => 'paid',
            $paid > 0 => 'partial',
            default => 'unpaid',
        };

        $this->update(['status' => $status]);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<BillingPeriod, $this>
     */
    public function period(): BelongsTo
    {
        return $this->belongsTo(BillingPeriod::class, 'billing_period_id');
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}

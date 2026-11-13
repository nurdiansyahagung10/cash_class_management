<?php

namespace App\Models;

use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['invoice_id', 'category_id', 'order_id', 'status', 'amount', 'qr_content', 'gateway_response', 'paid_at', 'proof_path', 'reviewed_by', 'reviewed_at', 'rejection_note'])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Separation of duties: a payment made by a staff member is verified by an admin only.
     */
    public function needsAdminReview(): bool
    {
        return $this->invoice->user->isStaff();
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    protected function casts(): array
    {
        return [
            'gateway_response' => 'array',
            'paid_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }
}

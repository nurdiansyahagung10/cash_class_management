<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\BillingPeriodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable(['category_id', 'title', 'amount', 'due_date', 'qris_url', 'created_by'])]
class BillingPeriod extends Model
{
    /** @use HasFactory<BillingPeriodFactory> */
    use HasFactory;

    /**
     * Deadlines follow the class's clock (WIB), not the app's UTC, so "midnight" is really midnight for members.
     */
    public const TIMEZONE = 'Asia/Jakarta';

    /**
     * Last day of the day/week/month holding the $no-th (1-based) payment of a schedule that begins on $startsOn.
     * Weeks run Monday to Sunday.
     */
    public static function scheduledDueDate(string $startsOn, string $unit, int $no): Carbon
    {
        $start = Carbon::parse($startsOn, self::TIMEZONE)->startOfDay();

        return match ($unit) {
            'day' => $start->addDays($no - 1),
            'month' => $start->startOfMonth()->addMonthsNoOverflow($no - 1)->endOfMonth()->startOfDay(),
            default => $start->startOfWeek(CarbonInterface::MONDAY)->addWeeks($no - 1)->endOfWeek(CarbonInterface::SUNDAY)->startOfDay(),
        };
    }

    /**
     * The last second a payment still counts as on time: 23:59:59 WIB on the due date.
     */
    public function dueAt(): Carbon
    {
        return Carbon::parse($this->due_date->toDateString(), self::TIMEZONE)->endOfDay();
    }

    public function isPastDue(): bool
    {
        return $this->dueAt()->isPast();
    }

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

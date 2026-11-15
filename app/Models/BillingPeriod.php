<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\BillingPeriodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable(['category_id', 'title', 'amount', 'starts_on', 'due_date', 'qris_url', 'created_by'])]
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
     * First day of the day/week/month holding the $no-th payment, i.e. the start of the range ending at scheduledDueDate().
     */
    public static function scheduledStartDate(string $startsOn, string $unit, int $no): Carbon
    {
        $start = Carbon::parse($startsOn, self::TIMEZONE)->startOfDay();

        return match ($unit) {
            'day' => $start->addDays($no - 1),
            'month' => $start->startOfMonth()->addMonthsNoOverflow($no - 1),
            default => $start->startOfWeek(CarbonInterface::MONDAY)->addWeeks($no - 1),
        };
    }

    /**
     * First day of this period's range. Periods created before ranges existed fall back to their creation day.
     */
    public function rangeStart(): Carbon
    {
        $due = Carbon::parse($this->due_date->toDateString(), self::TIMEZONE)->startOfDay();
        $start = $this->starts_on
            ? Carbon::parse($this->starts_on->toDateString(), self::TIMEZONE)->startOfDay()
            : $this->created_at->copy()->setTimezone(self::TIMEZONE)->startOfDay();

        return $start->greaterThan($due) ? $due : $start;
    }

    /**
     * Not yet open for payment: its range begins after today (WIB).
     */
    public function isIncoming(): bool
    {
        return $this->rangeStart()->isFuture();
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

    /**
     * Cash periods are shown as "Kas Minggu ke N" regardless of any legacy title
     * stored in the database. N follows the period's creation order in that category.
     */
    protected function title(): Attribute
    {
        return Attribute::get(function (?string $value) {
            if ($this->exists && $this->category?->isCash()) {
                $number = static::where('category_id', $this->category_id)
                    ->where('id', '<=', $this->id)
                    ->count();

                return 'Kas Minggu ke '.max(1, $number);
            }

            return $value ?? '';
        });
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
        return ['starts_on' => 'date', 'due_date' => 'date'];
    }
}

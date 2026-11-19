<?php

namespace App\Livewire;

use App\Services\Ledger;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Monthly cash calendar; switching months updates in place (no page reload) and stays in the URL as ?bulan=YYYY-MM.
 */
class CashCalendar extends Component
{
    #[Url(as: 'bulan', except: '')]
    public string $month = '';

    public function previousMonth(): void
    {
        $this->month = $this->monthStart()->subMonthNoOverflow()->format('Y-m');
    }

    public function nextMonth(): void
    {
        $this->month = $this->monthStart()->addMonthNoOverflow()->format('Y-m');
    }

    public function thisMonth(): void
    {
        $this->month = '';
    }

    /**
     * The requested month, or the current one when the value is missing or malformed.
     */
    public function monthStart(): Carbon
    {
        return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $this->month)
            ? Carbon::createFromFormat('Y-m-d', $this->month.'-01')->startOfDay()
            : now()->startOfMonth();
    }

    public function render(Ledger $ledger): View
    {
        return view('livewire.cash-calendar', [
            'calendar' => $ledger->calendarData($this->monthStart()),
        ]);
    }
}

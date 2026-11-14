<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\BillingPeriod;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PendingMember;
use App\Models\User;
use App\Services\Ledger;
use App\Services\PublicCashSummary;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, Ledger $ledger): View
    {
        $user = $request->user();

        if ($user === null) {
            return $this->publicDashboard($request, $ledger);
        }

        $unpaidInvoices = $user->isAdmin()
            ? collect()
            : $user->invoices()
                ->with(['period.category', 'payments'])
                ->whereIn('status', ['unpaid', 'partial'])
                ->get()
                ->sortBy(fn (Invoice $invoice) => $invoice->period->due_date->getTimestamp())
                ->values();

        $stats = [
            'balance' => $ledger->balance(),
            'brokenSeq' => $ledger->firstBrokenSeq(),
            'unpaidInvoices' => $unpaidInvoices,
            'cashCalendar' => $ledger->calendarData($this->requestedMonth($request)),
        ];

        if ($user->role !== UserRole::Admin) {
            $stats += [
                'paidInvoices' => $user->invoices()->where('status', 'paid')->count(),
                'unpaidTotal' => (int) $unpaidInvoices->sum(fn (Invoice $invoice) => $invoice->remainingAmount()),
                'pendingPayments' => Payment::where('status', 'pending')->whereNotNull('proof_path')->whereHas('invoice', fn ($query) => $query->where('user_id', $user->id))->count(),
                'latestPayments' => Payment::with(['invoice.period', 'category'])
                    ->whereHas('invoice', fn ($query) => $query->where('user_id', $user->id))
                    ->whereIn('id', Payment::query()
                        ->selectRaw('MAX(id)')
                        ->whereHas('invoice', fn ($query) => $query->where('user_id', $user->id))
                        ->groupBy('invoice_id'))
                    ->latest('id')
                    ->limit(5)
                    ->get(),
            ];
        }

        if ($user->canManageMembers()) {
            $stats += [
                'pendingMembers' => PendingMember::where('expires_at', '>', now())->count(),
                'activeMembers' => User::payableMembers()->count(),
            ];
        }

        if ($user->isAdmin()) {
            $allOpenInvoices = Invoice::whereHas('user', fn ($uq) => $uq->where('role', '!=', UserRole::Admin))
                ->with('payments')
                ->whereIn('status', ['unpaid', 'partial'])
                ->get();
            $invoiceStats = Invoice::whereHas('user', fn ($uq) => $uq->where('role', '!=', UserRole::Admin))
                ->selectRaw('COUNT(*) as invoice_count, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as paid_count', ['paid'])
                ->first();

            $stats += [
                'periodCount' => BillingPeriod::count(),
                'paidRate' => $invoiceStats->invoice_count === 0 ? 0 : (int) round($invoiceStats->paid_count * 100 / $invoiceStats->invoice_count),
                'unpaidTotal' => (int) $allOpenInvoices->sum(fn (Invoice $invoice) => $invoice->remainingAmount()),
                'pendingPayments' => Payment::where('status', 'pending')->whereNotNull('proof_path')->count(),
                'pendingMembers' => PendingMember::where('expires_at', '>', now())->count(),
                'latestPeriods' => BillingPeriod::withCount([
                    'invoices' => fn ($query) => $query->whereHas('user', fn ($uq) => $uq->where('role', '!=', UserRole::Admin)),
                    'invoices as paid_count' => fn ($query) => $query->whereHas('user', fn ($uq) => $uq->where('role', '!=', UserRole::Admin))->where('status', 'paid'),
                ])->latest('id')->limit(4)->get(),
            ];
        }

        if ($user->role === UserRole::Staff) {
            $stats['pendingPayments'] = Payment::where('status', 'pending')->whereNotNull('proof_path')->count();
        }

        return view('dashboard', ['user' => $user, ...$stats]);
    }

    /**
     * Class-wide cash summary for guests: aggregates only, no member names or personal invoices.
     */
    private function publicDashboard(Request $request, Ledger $ledger): View
    {
        return view('public-dashboard', [
            ...app(PublicCashSummary::class)->toArray(),
            'cashCalendar' => $ledger->calendarData($this->requestedMonth($request)),
        ]);
    }

    private function requestedMonth(Request $request): Carbon
    {
        $month = $request->query('bulan');

        return is_string($month) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)
            ? Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfDay()
            : now()->startOfMonth();
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\BillingPeriod;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PendingMember;
use App\Models\User;
use App\Services\Ledger;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, Ledger $ledger): View
    {
        $user = $request->user();
        $stats = [
            'balance' => $ledger->balance(),
            'brokenSeq' => $ledger->firstBrokenSeq(),
            'unpaidInvoices' => $user->invoices()->with('period')->where('status', 'unpaid')->latest('id')->get(),
            'cashCalendar' => $ledger->calendarData($this->requestedMonth($request)),
        ];

        if ($user->role === UserRole::User) {
            $stats += [
                'paidInvoices' => $user->invoices()->where('status', 'paid')->count(),
                'unpaidTotal' => $user->invoices()->where('status', 'unpaid')->sum('amount'),
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
                'activeMembers' => User::where('is_active', true)->count(),
            ];
        }

        if ($user->isAdmin()) {
            $invoiceStats = Invoice::query()
                ->selectRaw('COUNT(*) as invoice_count, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as paid_count, SUM(CASE WHEN status = ? THEN amount ELSE 0 END) as unpaid_total', ['paid', 'unpaid'])
                ->first();

            $stats += [
                'periodCount' => BillingPeriod::count(),
                'paidRate' => $invoiceStats->invoice_count === 0 ? 0 : (int) round($invoiceStats->paid_count * 100 / $invoiceStats->invoice_count),
                'unpaidTotal' => (int) $invoiceStats->unpaid_total,
                'pendingPayments' => Payment::where('status', 'pending')->whereNotNull('proof_path')->count(),
                'pendingMembers' => PendingMember::where('expires_at', '>', now())->count(),
                'latestPeriods' => BillingPeriod::withCount(['invoices', 'invoices as paid_count' => fn ($query) => $query->where('status', 'paid')])->latest('id')->limit(4)->get(),
            ];
        }

        if ($user->role === UserRole::Staff) {
            $stats['pendingPayments'] = Payment::where('status', 'pending')->whereNotNull('proof_path')->count();
        }

        return view('dashboard', ['user' => $user, ...$stats]);
    }

    private function requestedMonth(Request $request): Carbon
    {
        $month = $request->query('bulan');

        return is_string($month) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)
            ? Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfDay()
            : now()->startOfMonth();
    }
}

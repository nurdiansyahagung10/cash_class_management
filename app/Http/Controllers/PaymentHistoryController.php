<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentHistoryController extends Controller
{
    private const STAGES = ['incoming', 'running', 'paid'];

    /**
     * A member's own bills in three stages: not yet open (incoming), open and unpaid (running), and paid.
     */
    public function __invoke(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return redirect()->route('dashboard');
        }

        $invoices = $user->invoices()
            ->with(['period.category', 'payments'])
            ->get()
            ->filter(fn (Invoice $invoice) => $invoice->period !== null);

        $groups = [
            'incoming' => $invoices
                ->reject(fn (Invoice $i) => $i->isPaid())
                ->filter(fn (Invoice $i) => $i->period->isIncoming())
                ->sortBy(fn (Invoice $i) => $i->period->rangeStart()->getTimestamp()),
            'running' => $invoices
                ->reject(fn (Invoice $i) => $i->isPaid())
                ->reject(fn (Invoice $i) => $i->period->isIncoming())
                ->sortBy(fn (Invoice $i) => $i->period->due_date->getTimestamp()),
            'paid' => $invoices
                ->filter(fn (Invoice $i) => $i->isPaid())
                ->sortByDesc(fn (Invoice $i) => $i->payments->where('status', 'paid')->max('paid_at')?->getTimestamp() ?? $i->updated_at->getTimestamp()),
        ];

        $stage = $request->query('tahap');
        $stage = in_array($stage, self::STAGES, true) ? $stage : null;

        return view('payments.history', [
            'groups' => array_map(fn ($group) => $group->values(), $groups),
            'stage' => $stage,
            'visible' => $stage ? [$stage] : self::STAGES,
        ]);
    }
}

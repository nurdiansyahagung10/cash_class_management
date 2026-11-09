<?php

namespace App\Http\Controllers;

use App\Enums\LedgerEntryType;
use App\Enums\UserRole;
use App\Models\BillingPeriod;
use App\Models\Category;
use App\Models\User;
use App\Services\Ledger;
use App\Services\PaymentReminder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PeriodController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->canManageMembers(), 403);

        $search = trim((string) $request->query('q'));
        $status = $request->query('status');

        $query = BillingPeriod::with('category')->withCount([
            'invoices',
            'invoices as paid_count' => fn ($q) => $q->where('status', 'paid'),
        ]);

        if ($search !== '') {
            $query->where('title', 'like', "%{$search}%");
        }

        $periods = $query->latest('id')->get();

        if ($status === 'completed') {
            $periods = $periods->filter(fn ($p) => $p->invoices_count > 0 && $p->paid_count === $p->invoices_count);
        } elseif ($status === 'overdue') {
            $periods = $periods->filter(fn ($p) => $p->due_date->isPast() && $p->paid_count < $p->invoices_count);
        } elseif ($status === 'active') {
            $periods = $periods->filter(fn ($p) => ! $p->due_date->isPast() && $p->paid_count < $p->invoices_count);
        }

        $existingQrisList = BillingPeriod::whereNotNull('qris_url')
            ->select('qris_url', 'title', 'id')
            ->latest('id')
            ->get()
            ->unique('qris_url')
            ->values();

        return view('periods', [
            'periods' => $periods,
            'search' => $search,
            'statusFilter' => $status,
            'categories' => Category::orderBy('name')->get(),
            'activeMembers' => User::payableMembers()->count(),
            'latestQrisUrl' => $existingQrisList->first()?->qris_url,
            'existingQrisList' => $existingQrisList,
        ]);
    }

    public function show(BillingPeriod $period, Request $request, PaymentReminder $reminder): View
    {
        abort_unless($request->user()->canManageMembers(), 403);

        $filter = (string) $request->query('status', '');

        $allInvoices = $period->invoices()
            ->whereHas('user', fn ($q) => $q->where('role', '!=', UserRole::Admin))
            ->with(['user', 'period', 'payments' => fn ($q) => $q->latest('id')])
            ->get()
            ->sortBy(fn ($inv) => (int) ($inv->user->student_no ?? 999))
            ->values();

        $unpaidInvoices = $allInvoices->filter(fn ($inv) => ! $inv->isPaid())->values();

        $invoices = match ($filter) {
            'paid' => $allInvoices->filter(fn ($inv) => $inv->isPaid())->values(),
            'partial' => $allInvoices->filter(fn ($inv) => $inv->isPartial())->values(),
            'unpaid' => $unpaidInvoices,
            'overdue' => $allInvoices->filter(fn ($inv) => $inv->isOverdue())->values(),
            default => $allInvoices,
        };

        $totalCount = $allInvoices->count();
        $paidCount = $allInvoices->where('status', 'paid')->count();
        $partialCount = $allInvoices->where('status', 'partial')->count();
        $unpaidCount = $totalCount - $paidCount;
        $collectedAmount = (int) $allInvoices->sum(fn ($inv) => $inv->paidAmount());

        return view('periods.show', [
            'period' => $period->load('category'),
            'invoices' => $invoices,
            'filter' => $filter,
            'totalCount' => $totalCount,
            'paidCount' => $paidCount,
            'partialCount' => $partialCount,
            'unpaidCount' => $unpaidCount,
            'totalAmount' => $totalCount * $period->amount,
            'collectedAmount' => $collectedAmount,
            'unpaidInvoices' => $unpaidInvoices,
            'reminder' => $reminder,
        ]);
    }

    public function store(Request $request, Ledger $ledger): RedirectResponse
    {
        abort_unless($request->user()->canManageMembers(), 403);

        $latestQrisUrl = BillingPeriod::whereNotNull('qris_url')->latest('id')->value('qris_url');
        $qrisRequiredRule = ($latestQrisUrl || $request->filled('existing_qris')) ? 'nullable' : 'required_without_all:qris_image,qris_payload';

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'new_category' => ['nullable', 'string', 'max:100'],
            'amount' => ['required', 'integer', 'min:1000', 'max:1000000'],
            'due_date' => ['required', 'date', 'after_or_equal:today'],
            'existing_qris' => ['nullable', 'string', 'max:4096'],
            'qris_image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'qris_payload' => ['nullable', 'string', 'max:4096'],
            'qris_url' => ['nullable', 'string', 'max:4096', $qrisRequiredRule],
        ]);

        if (! empty($validated['qris_url']) && ! str_starts_with(trim($validated['qris_url']), '000201') && ! filter_var($validated['qris_url'], FILTER_VALIDATE_URL)) {
            return back()->withErrors(['qris_url' => 'Format URL atau string QRIS tidak valid.'])->withInput();
        }

        if (! empty($validated['new_category'])) {
            $cat = Category::firstOrCreate(['name' => trim($validated['new_category'])]);
            $validated['category_id'] = $cat->id;
        }
        unset($validated['new_category']);

        if (empty($validated['category_id'])) {
            $defaultCat = Category::firstOrCreate(['name' => 'Uang Kas']);
            $validated['category_id'] = $defaultCat->id;
        }

        if (! empty($validated['qris_payload'])) {
            $validated['qris_url'] = trim($validated['qris_payload']);
        } elseif ($request->hasFile('qris_image')) {
            $path = $request->file('qris_image')->store('qris', 'public');
            $validated['qris_url'] = asset('storage/'.$path);
        } elseif (! empty($validated['qris_url'])) {
            $validated['qris_url'] = trim($validated['qris_url']);
        } elseif (! empty($validated['existing_qris'])) {
            $validated['qris_url'] = trim($validated['existing_qris']);
        } elseif ($latestQrisUrl) {
            $validated['qris_url'] = $latestQrisUrl;
        }
        unset($validated['qris_image'], $validated['qris_payload'], $validated['existing_qris']);

        $period = DB::transaction(function () use ($validated, $request, $ledger) {
            $period = BillingPeriod::create([...$validated, 'created_by' => $request->user()->id]);

            $members = User::payableMembers()->pluck('id');
            $period->invoices()->createMany(
                $members->map(fn (int $id) => ['user_id' => $id, 'amount' => $period->amount])->all()
            );

            $period->load('category');

            $ledger->append(
                LedgerEntryType::InvoiceCreated,
                [
                    'period_id' => $period->id,
                    'title' => $period->title,
                    'category' => $period->category?->name ?? 'Uang Kas',
                    'amount' => $period->amount,
                    'members' => $members->count(),
                ],
                $request->user(),
                'billing_periods',
                $period->id,
            );

            return $period;
        });

        return back()->with('status', "{$period->title} ({$period->category?->name}): tagihan dibuat untuk {$period->invoices()->count()} anggota aktif.");
    }
}

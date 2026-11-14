<?php

namespace App\Http\Controllers;

use App\Enums\LedgerEntryType;
use App\Enums\UserRole;
use App\Models\BillingPeriod;
use App\Models\Category;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Services\Ledger;
use App\Services\PaymentReminder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
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
            'invoices as paid_count' => fn($q) => $q->where('status', 'paid'),
        ]);

        if ($search !== '') {
            $query->where('title', 'like', "%{$search}%");
        }

        $periods = $query->latest('id')->get();

        if ($status === 'completed') {
            $periods = $periods->filter(fn($p) => $p->invoices_count > 0 && $p->paid_count === $p->invoices_count);
        } elseif ($status === 'overdue') {
            $periods = $periods->filter(fn($p) => $p->isPastDue() && $p->paid_count < $p->invoices_count);
        } elseif ($status === 'active') {
            $periods = $periods->filter(fn($p) => !$p->isPastDue() && $p->paid_count < $p->invoices_count);
        }

        $perPage = 8;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $periods = (new LengthAwarePaginator(
            $periods->forPage($page, $perPage)->values(),
            $periods->count(),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        ))->withQueryString();

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
            ->whereHas('user', fn($q) => $q->where('role', '!=', UserRole::Admin))
            ->with(['user', 'period', 'payments' => fn($q) => $q->latest('id')])
            ->get()
            ->sortBy(fn($inv) => (int) ($inv->user->student_no ?? 999))
            ->values();

        $unpaidInvoices = $allInvoices->filter(fn($inv) => !$inv->isPaid())->values();

        $invoices = match ($filter) {
            'paid' => $allInvoices->filter(fn($inv) => $inv->isPaid())->values(),
            'partial' => $allInvoices->filter(fn($inv) => $inv->isPartial())->values(),
            'unpaid' => $unpaidInvoices,
            'overdue' => $allInvoices->filter(fn($inv) => $inv->isOverdue())->values(),
            default => $allInvoices,
        };

        $totalCount = $allInvoices->count();
        $paidCount = $allInvoices->where('status', 'paid')->count();
        $partialCount = $allInvoices->where('status', 'partial')->count();
        $unpaidCount = $totalCount - $paidCount;
        $collectedAmount = (int) $allInvoices->sum(fn($inv) => $inv->paidAmount());

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
        $isSeries = $request->integer('installments', 1) > 1;

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:240'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'new_category' => ['nullable', 'string', 'max:100'],
            'amount' => ['required', 'integer', 'min:1000', 'max:1000000'],
            'due_date' => [$isSeries ? 'nullable' : 'required', 'date', 'after_or_equal:today'],
            'installments' => ['nullable', 'integer', 'min:1', 'max:31'],
            'installment_unit' => [$isSeries ? 'required' : 'nullable', 'in:day,week,month'],
            'amount_mode' => ['nullable', 'in:each,total'],
            'starts_on' => ['nullable', 'date', 'after_or_equal:today'],
            'existing_qris' => ['nullable', 'string', 'max:4096'],
            'qris_image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'qris_payload' => ['nullable', 'string', 'max:4096'],
            'qris_url' => ['nullable', 'string', 'max:4096', $qrisRequiredRule],
        ]);

        if (!empty($validated['qris_url']) && !str_starts_with(trim($validated['qris_url']), '000201') && !filter_var($validated['qris_url'], FILTER_VALIDATE_URL)) {
            return back()->withErrors(['qris_url' => 'Format URL atau string QRIS tidak valid.'])->withInput();
        }

        $count = $isSeries ? (int) $validated['installments'] : 1;
        $amounts = array_fill(0, $count, (int) $validated['amount']);
        if ($isSeries && ($validated['amount_mode'] ?? 'each') === 'total') {
            $each = intdiv((int) $validated['amount'], $count);
            $amounts = array_fill(0, $count, $each);
            $amounts[$count - 1] += (int) $validated['amount'] - $each * $count;
        }

        if (min($amounts) < 1000) {
            return back()->withErrors(['amount' => 'Nominal tiap pembayaran minimal Rp1.000. Kurangi jumlah pembayaran atau naikkan nominal.'])->withInput();
        }

        $startsOn = $validated['starts_on'] ?? now(BillingPeriod::TIMEZONE)->toDateString();
        $dueDates = $isSeries
            ? array_map(fn(int $no) => BillingPeriod::scheduledDueDate($startsOn, $validated['installment_unit'], $no)->toDateString(), range(1, $count))
            : [$validated['due_date']];

        if (!empty($validated['new_category'])) {
            $cat = Category::firstOrCreate(['name' => trim($validated['new_category'])]);
            $validated['category_id'] = $cat->id;
        }

        if (empty($validated['category_id'])) {
            $defaultCat = Category::firstOrCreate(['name' => 'Uang Kas']);
            $validated['category_id'] = $defaultCat->id;
        }

        if (!empty($validated['qris_payload'])) {
            $validated['qris_url'] = trim($validated['qris_payload']);
        } elseif ($request->hasFile('qris_image')) {
            $path = $request->file('qris_image')->store('qris', 'public');
            $validated['qris_url'] = asset('storage/' . $path);
        } elseif (!empty($validated['qris_url'])) {
            $validated['qris_url'] = trim($validated['qris_url']);
        } elseif (!empty($validated['existing_qris'])) {
            $validated['qris_url'] = trim($validated['existing_qris']);
        } elseif ($latestQrisUrl) {
            $validated['qris_url'] = $latestQrisUrl;
        }

        $periods = DB::transaction(function () use ($validated, $dueDates, $amounts, $count, $isSeries, $request, $ledger) {
            $members = User::payableMembers()->pluck('id');

            return collect($dueDates)->map(function (string $dueDate, int $index) use ($validated, $amounts, $count, $isSeries, $members, $request, $ledger) {
                $period = BillingPeriod::create([
                    'category_id' => $validated['category_id'],
                    'title' => $isSeries ? sprintf('%s (%d/%d)', $validated['title'], $index + 1, $count) : $validated['title'],
                    'amount' => $amounts[$index],
                    'due_date' => $dueDate,
                    'qris_url' => $validated['qris_url'] ?? null,
                    'created_by' => $request->user()->id,
                ]);

                $period->invoices()->createMany(
                    $members->map(fn(int $id) => ['user_id' => $id, 'amount' => $period->amount])->all()
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
                        ...($isSeries ? ['series' => ($index + 1) . '/' . $count] : []),
                    ],
                    $request->user(),
                    'billing_periods',
                    $period->id,
                );

                return $period;
            });
        });

        $first = $periods->first();
        $memberCount = $first->invoices()->count();

        return back()->with('status', $isSeries
            ? "{$validated['title']}: {$count} tagihan dibuat untuk {$memberCount} anggota aktif, tenggat terakhir " . $periods->last()->due_date->format('d M Y') . ' pukul 23.59 WIB.'
            : "{$first->title} ({$first->category?->name}): tagihan dibuat untuk {$memberCount} anggota aktif.");
    }

    public function destroy(BillingPeriod $period, Request $request, Ledger $ledger): RedirectResponse
    {
        abort_unless($request->user()->canManageMembers(), 403);

        $created = LedgerEntry::where('type', LedgerEntryType::InvoiceCreated)
            ->where('ref_table', 'billing_periods')
            ->where('ref_id', $period->id)
            ->first();

        $cancelled = DB::transaction(function () use ($period, $created, $request, $ledger) {
            if (!BillingPeriod::lockForUpdate()->find($period->id)?->canBeCancelled()) {
                return false;
            }

            $period->invoices()->delete();
            $period->delete();

            $ledger->append(
                LedgerEntryType::Reversal,
                [
                    'reversed_seq' => $created?->seq,
                    'type' => LedgerEntryType::InvoiceCreated->value,
                    'amount' => 0,
                    'reason' => "Tagihan \"{$period->title}\" dibatalkan",
                ],
                $request->user(),
                'billing_periods',
                $period->id,
            );

            return true;
        });

        if (!$cancelled) {
            return back()->with('error', 'Tagihan tidak dapat dibatalkan: sudah lewat 1 hari atau sudah ada pembayaran.');
        }

        return redirect()->route('periods.index')->with('status', "Tagihan {$period->title} dibatalkan.");
    }
}

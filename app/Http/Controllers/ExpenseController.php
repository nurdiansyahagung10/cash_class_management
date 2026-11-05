<?php

namespace App\Http\Controllers;

use App\Enums\LedgerEntryType;
use App\Models\Expense;
use App\Services\Ledger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExpenseController extends Controller
{
    public function index(Request $request, Ledger $ledger): View
    {
        $search = trim((string) $request->query('q'));

        $expenses = Expense::with('creator')
            ->when($search !== '', fn ($query) => $query->where(
                fn ($q) => $q->where('title', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
            ))
            ->latest('spent_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('expenses.index', [
            'expenses' => $expenses,
            'totalExpense' => Expense::sum('amount'),
            'balance' => $ledger->balance(),
            'search' => $search,
        ]);
    }

    public function store(Request $request, Ledger $ledger): RedirectResponse
    {
        abort_unless($request->user()->canManageMembers(), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'integer', 'min:500', 'max:100000000'],
            'spent_at' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:500'],
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        if ($request->hasFile('receipt')) {
            $validated['receipt_path'] = $request->file('receipt')->store('receipts');
        }
        unset($validated['receipt']);

        if ($validated['amount'] > $ledger->balance()) {
            return back()->withInput()->withErrors([
                'amount' => 'Nominal pengeluaran melebihi saldo kas tersedia (Rp'.number_format($ledger->balance(), 0, ',', '.').').',
            ]);
        }

        $expense = DB::transaction(function () use ($validated, $request, $ledger) {
            $expense = Expense::create([
                ...$validated,
                'created_by' => $request->user()->id,
            ]);

            $ledger->append(
                LedgerEntryType::ExpenseApproved,
                [
                    'expense_id' => $expense->id,
                    'title' => $expense->title,
                    'amount' => $expense->amount,
                    'spent_at' => $expense->spent_at->toDateString(),
                ],
                $request->user(),
                'expenses',
                $expense->id,
            );

            return $expense;
        });

        return back()->with('status', "Pengeluaran '{$expense->title}' (Rp".number_format($expense->amount, 0, ',', '.').') berhasil dicatat dan masuk ledger.');
    }

    public function receipt(Expense $expense): StreamedResponse|BinaryFileResponse
    {
        abort_unless($expense->receipt_path && Storage::exists($expense->receipt_path), 404);

        return Storage::response($expense->receipt_path);
    }
}

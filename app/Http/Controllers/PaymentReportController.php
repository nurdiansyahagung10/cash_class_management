<?php

namespace App\Http\Controllers;

use App\Enums\LedgerEntryType;
use App\Enums\UserRole;
use App\Models\BillingPeriod;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Services\Ledger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentReportController extends Controller
{
    public function index(Request $request, Ledger $ledger): View
    {
        $allInvoices = $this->filteredInvoices(
            $request,
            applyStatusFilter: false
        );

        $filteredInvoices = $this->applyStatusFilter(
            $allInvoices,
            (string) $request->query('status', '')
        );

        $perPage = 10;
        $pageName = 'invoice_page';
        $currentPage = LengthAwarePaginator::resolveCurrentPage($pageName);

        $invoices = new LengthAwarePaginator(
            $filteredInvoices->forPage($currentPage, $perPage)->values(),
            $filteredInvoices->count(),
            $perPage,
            $currentPage,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => $pageName,
            ]
        );

        $invoices->withQueryString();

        $totalExpected = (int) $allInvoices->sum('amount');
        $totalCollected = (int) $allInvoices->sum(fn (Invoice $inv) => $inv->paidAmount());
        $totalRemaining = max(0, $totalExpected - $totalCollected);
        $collectionRate = $totalExpected > 0 ? (int) round($totalCollected * 100 / $totalExpected) : 0;

        $overdueInvoices = $allInvoices->filter(fn (Invoice $inv) => $inv->isOverdue());

        $periodSummaries = BillingPeriod::with([
            'category',
            'invoices' => fn ($q) => $q->whereHas('user', fn ($uq) => $uq->where('role', '!=', UserRole::Admin)),
            'invoices.payments',
        ])
            ->when($request->filled('period_id'), fn ($q) => $q->where('id', $request->integer('period_id')))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->integer('category_id')))
            ->latest('id')
            ->get()
            ->map(function (BillingPeriod $period): array {
                $target = (int) $period->invoices->sum('amount');
                $collected = (int) $period->invoices->sum(fn (Invoice $inv) => $inv->paidAmount());
                $paidCount = $period->invoices->where('status', 'paid')->count();
                $partialCount = $period->invoices->where('status', 'partial')->count();
                $unpaidCount = $period->invoices->where('status', 'unpaid')->count();
                $isOverdue = $period->due_date->endOfDay()->isPast() && $paidCount < $period->invoices->count();

                return [
                    'period' => $period,
                    'target' => $target,
                    'collected' => $collected,
                    'remaining' => max(0, $target - $collected),
                    'total_count' => $period->invoices->count(),
                    'paid_count' => $paidCount,
                    'partial_count' => $partialCount,
                    'unpaid_count' => $unpaidCount,
                    'is_overdue' => $isOverdue,
                ];
            });

        // Data Buku Kas Umum (BKU)
        $bkuData = $this->buildBkuData();

        // Data Matriks Iuran Kas Siswa
        $matrixData = $this->buildMatrixData();

        // Data Kas Kelas
        $totalExpense = (int) Expense::sum('amount');
        $realCashBalance = $ledger->balance();

        return view('reports.payments', [
            'activeTab' => (string) $request->query('tab', 'detail'),
            'invoices' => $invoices,
            'periodSummaries' => $periodSummaries,
            'periods' => BillingPeriod::orderByDesc('id')->get(),
            'categories' => Category::orderBy('name')->get(),
            'selectedPeriodId' => $request->integer('period_id') ?: null,
            'selectedCategoryId' => $request->integer('category_id') ?: null,
            'statusFilter' => (string) $request->query('status', ''),
            'search' => trim((string) $request->query('q', '')),
            'totalExpected' => $totalExpected,
            'totalCollected' => $totalCollected,
            'totalRemaining' => $totalRemaining,
            'collectionRate' => $collectionRate,
            'totalExpense' => $totalExpense,
            'realCashBalance' => $realCashBalance,
            'totalCount' => $allInvoices->count(),
            'paidCount' => $allInvoices->where('status', 'paid')->count(),
            'partialCount' => $allInvoices->where('status', 'partial')->count(),
            'unpaidCount' => $allInvoices->where('status', 'unpaid')->count(),
            'overdueCount' => $overdueInvoices->count(),
            'overdueRemaining' => (int) $overdueInvoices->sum(fn (Invoice $inv) => $inv->remainingAmount()),
            'bkuData' => $bkuData,
            'matrixData' => $matrixData,
            ...$this->signoffData($request),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $invoices = $this->filteredInvoices($request, applyStatusFilter: true);
        $filename = 'laporan-pembayaran-kas-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($invoices) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'No Absen',
                'Nama Anggota',
                'Periode',
                'Kategori',
                'Total Tagihan',
                'Sudah Dibayar',
                'Sisa Tagihan',
                'Jumlah Cicilan Masuk',
                'Status Pembayaran',
                'Jatuh Tempo',
                'Kondisi Tenggat',
            ]);

            foreach ($invoices as $invoice) {
                $paidAmount = $invoice->paidAmount();
                $remaining = $invoice->remainingAmount();
                $installmentsCount = $invoice->payments->where('status', 'paid')->count();

                $statusLabel = match (true) {
                    $invoice->isPaid() => 'Lunas',
                    $invoice->isPartial() => 'Menyicil',
                    default => 'Belum Bayar',
                };

                $deadlineCondition = match (true) {
                    $invoice->isPaid() => 'Selesai Tepat Waktu / Lunas',
                    $invoice->isOverdue() => 'Lewat Tenggat ('.$invoice->overdueDays().' hari) - Wajib Pelunasan Penuh',
                    default => 'Masih Dalam Tenggat (Bisa Menyicil)',
                };

                fputcsv($handle, [
                    $invoice->user->student_no ?? '-',
                    $invoice->user->name,
                    $invoice->period->title,
                    $invoice->period->category->name ?? 'Uang Kas',
                    $invoice->amount,
                    $paidAmount,
                    $remaining,
                    $installmentsCount,
                    $statusLabel,
                    $invoice->period->due_date->format('Y-m-d'),
                    $deadlineCondition,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Ekspor Buku Kas Umum (BKU) Standar Kas Sekolah Indonesia
     */
    public function exportBku(Request $request): StreamedResponse
    {
        $bkuData = $this->buildBkuData();
        $filename = 'buku-kas-umum-bku-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($bkuData) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['BUKU KAS UMUM (BKU) KAS KELAS 1A']);
            fputcsv($handle, ['Tanggal Ekspor: '.now()->format('d/m/Y H:i')]);
            fputcsv($handle, []);

            fputcsv($handle, [
                'No',
                'Tanggal',
                'No. Bukti / Ref',
                'Uraian Transaksi',
                'Penerimaan (Debet)',
                'Pengeluaran (Kredit)',
                'Saldo Kas',
            ]);

            foreach ($bkuData['entries'] as $row) {
                fputcsv($handle, [
                    $row['no'],
                    $row['date'],
                    $row['ref'],
                    $row['description'],
                    $row['debit'],
                    $row['credit'],
                    $row['balance'],
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['TOTAL PENERIMAAN (DEBET)', '', '', '', $bkuData['totalDebit'], '', '']);
            fputcsv($handle, ['TOTAL PENGELUARAN (KREDIT)', '', '', '', '', $bkuData['totalCredit'], '']);
            fputcsv($handle, ['SALDO KAS TERSEDIA', '', '', '', '', '', $bkuData['endingBalance']]);

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Ekspor Matriks Rekapitulasi Iuran Siswa per Periode (Buku Pembantu Iuran Kas)
     */
    public function exportMatrix(Request $request): StreamedResponse
    {
        $matrixData = $this->buildMatrixData();
        $filename = 'matriks-iuran-kas-siswa-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($matrixData) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['MATRIKS REKAPITULASI IURAN KAS SISWA KELAS 1A']);
            fputcsv($handle, ['Tanggal Ekspor: '.now()->format('d/m/Y H:i')]);
            fputcsv($handle, []);

            $headers = ['No', 'No. Absen / NIM', 'Nama Siswa'];
            foreach ($matrixData['periods'] as $period) {
                $headers[] = $period->title.' (Rp'.number_format($period->amount, 0, ',', '.').')';
            }
            $headers[] = 'Total Disetor';
            $headers[] = 'Sisa Tunggakan';
            $headers[] = 'Status';

            fputcsv($handle, $headers);

            foreach ($matrixData['rows'] as $row) {
                $line = [
                    $row['no'],
                    $row['user']->student_no ?? '-',
                    $row['user']->name,
                ];

                foreach ($matrixData['periods'] as $period) {
                    $cell = $row['cells'][$period->id] ?? null;
                    if (! $cell || $cell['status'] === 'none') {
                        $line[] = '-';
                    } elseif ($cell['status'] === 'paid') {
                        $line[] = 'Lunas ('.$cell['paid'].')';
                    } elseif ($cell['status'] === 'partial') {
                        $line[] = 'Cicil ('.$cell['paid'].'/Sisa '.$cell['remaining'].')';
                    } else {
                        $line[] = 'Belum ('.$cell['remaining'].')';
                    }
                }

                $line[] = $row['total_paid'];
                $line[] = $row['total_remaining'];
                $line[] = $row['status'];

                fputcsv($handle, $line);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function signoffData(Request $request): array
    {
        return [
            'waliKelas' => 'Wali Kelas 1A',
            'ketuaKelas' => 'Ketua Kelas 1A',
            'bendaharaKelas' => 'Bendahara Kelas 1A',
        ];
    }

    /**
     * Membangun dataset Buku Kas Umum (BKU) sesuai standar tata kelola kas sekolah
     *
     * @return array{
     *     entries: array<int, array{no: int, seq: int, date: string, date_full: string, ref: string, description: string, debit: int, credit: int, balance: int}>,
     *     totalDebit: int,
     *     totalCredit: int,
     *     endingBalance: int
     * }
     */
    public function buildBkuData(): array
    {
        $bkuTypes = [
            LedgerEntryType::PaymentConfirmed->value,
            LedgerEntryType::ExpenseApproved->value,
            LedgerEntryType::Reversal->value,
        ];

        $entries = LedgerEntry::query()
            ->whereIn('type', $bkuTypes)
            ->orderBy('seq', 'asc')
            ->get();

        $runningBalance = 0;
        $bkuEntries = [];
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($entries as $index => $entry) {
            $payload = json_decode($entry->payload, true) ?? [];
            $amount = (int) ($payload['amount'] ?? 0);
            $debit = 0;
            $credit = 0;
            $ref = 'LED-'.$entry->seq;
            $desc = '';

            if ($entry->type === LedgerEntryType::PaymentConfirmed) {
                $debit = $amount;
                $runningBalance += $amount;
                $totalDebit += $amount;
                $name = $payload['name'] ?? 'Anggota';
                $studentNo = $payload['student_no'] ?? '';
                $periodTitle = $payload['period_title'] ?? $payload['period'] ?? 'Uang Kas';
                $viaStaff = ! empty($payload['paid_by_staff_name']) ? " (via Bendahara: {$payload['paid_by_staff_name']})" : '';
                $desc = "Penerimaan iuran kas: {$name}".($studentNo !== '' ? " ({$studentNo})" : '')." — {$periodTitle}{$viaStaff}";
                if (! empty($payload['order_id'])) {
                    $ref = $payload['order_id'];
                }
            } elseif ($entry->type === LedgerEntryType::ExpenseApproved) {
                $credit = $amount;
                $runningBalance -= $amount;
                $totalCredit += $amount;
                $title = $payload['title'] ?? 'Pengeluaran kas kelas';
                $desc = "Pengeluaran kas: {$title}";
                if (! empty($payload['expense_id'])) {
                    $ref = 'EXP-'.$payload['expense_id'];
                }
            } elseif ($entry->type === LedgerEntryType::Reversal) {
                $reversedType = $payload['type'] ?? '';
                $reason = $payload['reason'] ?? 'Koreksi transaksi';
                $revSeq = $payload['reversed_seq'] ?? $payload['seq'] ?? '';
                if ($reversedType === LedgerEntryType::PaymentConfirmed->value) {
                    $credit = $amount;
                    $runningBalance -= $amount;
                    $totalCredit += $amount;
                    $desc = "Koreksi/Pembatalan kas masuk #{$revSeq}: {$reason}";
                } elseif ($reversedType === LedgerEntryType::ExpenseApproved->value) {
                    $debit = $amount;
                    $runningBalance += $amount;
                    $totalDebit += $amount;
                    $desc = "Koreksi/Pengembalian kas keluar #{$revSeq}: {$reason}";
                }
                $ref = 'REV-'.$entry->seq;
            }

            $dateCarbon = Carbon::parse($entry->created_at);

            $bkuEntries[] = [
                'no' => $index + 1,
                'seq' => $entry->seq,
                'date' => $dateCarbon->format('d/m/Y'),
                'date_full' => $dateCarbon->format('d M Y H:i'),
                'ref' => $ref,
                'description' => $desc,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $runningBalance,
            ];
        }

        return [
            'entries' => $bkuEntries,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'endingBalance' => $runningBalance,
        ];
    }

    /**
     * Membangun dataset Matriks Rekapitulasi Iuran Siswa (Buku Pembantu Iuran Kas)
     *
     * @return array{
     *     periods: Collection<int, BillingPeriod>,
     *     rows: array<int, array{no: int, user: User, cells: array<int, array{status: string, paid: int, target: int, remaining: int, is_overdue: bool, invoice_id?: int}>, total_target: int, total_paid: int, total_remaining: int, status: string}>,
     *     periodTotals: array<int, int>,
     *     totalTarget: int,
     *     totalPaid: int,
     *     totalRemaining: int
     * }
     */
    public function buildMatrixData(): array
    {
        $allMembers = User::payableMembers()
            ->orderByRaw('CAST(student_no AS UNSIGNED) ASC, student_no ASC')
            ->get();

        $allPeriods = BillingPeriod::with('category')->orderBy('id', 'asc')->get();

        $invoicesWithPayments = Invoice::with(['payments' => fn ($q) => $q->where('status', 'paid')])
            ->whereHas('user', fn ($q) => $q->where('role', '!=', UserRole::Admin))
            ->get();
        $invoicesMap = [];
        foreach ($invoicesWithPayments as $inv) {
            $invoicesMap[$inv->user_id][$inv->billing_period_id] = $inv;
        }

        $matrixRows = [];
        $periodTotals = array_fill_keys($allPeriods->pluck('id')->all(), 0);
        $overallTarget = 0;
        $overallPaid = 0;

        foreach ($allMembers as $idx => $member) {
            $cells = [];
            $studentPaid = 0;
            $studentTarget = 0;

            foreach ($allPeriods as $period) {
                $inv = $invoicesMap[$member->id][$period->id] ?? null;
                if (! $inv) {
                    $cells[$period->id] = [
                        'status' => 'none',
                        'paid' => 0,
                        'target' => 0,
                        'remaining' => 0,
                        'is_overdue' => false,
                    ];

                    continue;
                }

                $paid = $inv->paidAmount();
                $target = (int) $inv->amount;
                $rem = $inv->remainingAmount();

                $studentPaid += $paid;
                $studentTarget += $target;
                $periodTotals[$period->id] += $paid;

                $cells[$period->id] = [
                    'status' => $inv->status,
                    'paid' => $paid,
                    'target' => $target,
                    'remaining' => $rem,
                    'is_overdue' => $inv->isOverdue(),
                    'invoice_id' => $inv->id,
                ];
            }

            $overallTarget += $studentTarget;
            $overallPaid += $studentPaid;

            $studentRemaining = max(0, $studentTarget - $studentPaid);
            $statusText = match (true) {
                $studentTarget > 0 && $studentRemaining === 0 => 'Lunas',
                $studentPaid > 0 => 'Menyicil',
                $studentTarget > 0 => 'Belum Bayar',
                default => 'Bebas Kas',
            };

            $matrixRows[] = [
                'no' => $idx + 1,
                'user' => $member,
                'cells' => $cells,
                'total_target' => $studentTarget,
                'total_paid' => $studentPaid,
                'total_remaining' => $studentRemaining,
                'status' => $statusText,
            ];
        }

        return [
            'periods' => $allPeriods,
            'rows' => $matrixRows,
            'periodTotals' => $periodTotals,
            'totalTarget' => $overallTarget,
            'totalPaid' => $overallPaid,
            'totalRemaining' => max(0, $overallTarget - $overallPaid),
        ];
    }

    /**
     * @return Collection<int, Invoice>
     */
    private function filteredInvoices(Request $request, bool $applyStatusFilter): Collection
    {
        $periodId = $request->integer('period_id') ?: null;
        $categoryId = $request->integer('category_id') ?: null;
        $search = trim((string) $request->query('q', ''));

        $invoices = Invoice::with(['user', 'period.category', 'payments' => fn ($q) => $q->latest('id')])
            ->whereHas('user', fn ($uq) => $uq->where('role', '!=', UserRole::Admin))
            ->when($periodId, fn ($q) => $q->where('billing_period_id', $periodId))
            ->when($categoryId, fn ($q) => $q->whereHas('period', fn ($pq) => $pq->where('category_id', $categoryId)))
            ->when($search !== '', fn ($q) => $q->whereHas('user', fn ($uq) => $uq
                ->where('role', '!=', UserRole::Admin)
                ->where(fn ($sq) => $sq->where('name', 'like', "%{$search}%")
                    ->orWhere('student_no', 'like', "%{$search}%"))))
            ->latest('id')
            ->get()
            ->sortBy([
                fn (Invoice $inv) => -(int) $inv->billing_period_id,
                fn (Invoice $inv) => (int) ($inv->user->student_no ?? 999),
            ])
            ->values();

        if (! $applyStatusFilter) {
            return $invoices;
        }

        return $this->applyStatusFilter($invoices, (string) $request->query('status', ''));
    }

    /**
     * @param  Collection<int, Invoice>  $invoices
     * @return Collection<int, Invoice>
     */
    private function applyStatusFilter(Collection $invoices, string $status): Collection
    {
        return match ($status) {
            'paid' => $invoices->filter(fn (Invoice $inv) => $inv->isPaid())->values(),
            'partial' => $invoices->filter(fn (Invoice $inv) => $inv->isPartial())->values(),
            'unpaid' => $invoices->filter(fn (Invoice $inv) => $inv->status === 'unpaid')->values(),
            'overdue' => $invoices->filter(fn (Invoice $inv) => $inv->isOverdue())->values(),
            default => $invoices,
        };
    }
}

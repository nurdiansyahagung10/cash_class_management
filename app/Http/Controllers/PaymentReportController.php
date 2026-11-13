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
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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

        $activeTab = (string) $request->query('tab', 'detail');
        $bkuData = $activeTab === 'bku' ? $this->buildBkuData($request) : null;
        $matrixData = $activeTab === 'matrix' ? $this->buildMatrixData($request) : null;

        // Data Kas Kelas
        $totalExpense = (int) Expense::sum('amount');
        $realCashBalance = $ledger->balance();

        return view('reports.payments', [
            'activeTab' => $activeTab,
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
     * Laporan PDF terformat (kop, ringkasan, tabel, tanda tangan) untuk tab aktif.
     * `inline=1` membuka PDF di browser agar bisa langsung dicetak.
     */
    public function pdf(Request $request): Response
    {
        $tab = in_array($request->query('tab'), ['matrix', 'bku'], true) ? $request->query('tab') : 'detail';

        $data = [
            'generatedAt' => now(),
            'printedBy' => $request->user()->name,
            ...$this->signoffData($request),
        ];

        if ($tab === 'bku') {
            $data += [
                'title' => 'Buku Kas Umum (BKU)',
                'subtitle' => 'Jurnal kronologis penerimaan, pengeluaran, dan saldo kas kelas.',
                'bkuData' => $this->buildBkuData(),
            ];
        } elseif ($tab === 'matrix') {
            $data += [
                'title' => 'Matriks Rekapitulasi Iuran Siswa',
                'subtitle' => 'Status setoran kas setiap anggota per periode tagihan.',
                'matrixData' => $this->buildMatrixData(),
            ];
        } else {
            $invoices = $this->filteredInvoices($request, applyStatusFilter: true)->values();
            $totalPaid = (int) $invoices->sum(fn (Invoice $inv) => $inv->paidAmount());
            $data += [
                'title' => 'Rincian Pembayaran & Cicilan Anggota',
                'subtitle' => 'Menurut filter yang sedang dipilih di halaman laporan.',
                'invoices' => $invoices,
                'totalTarget' => (int) $invoices->sum('amount'),
                'totalPaid' => $totalPaid,
            ];
        }

        $pdf = Pdf::loadView("reports.pdf.{$tab}", $data)
            ->setPaper('a4', $tab === 'matrix' ? 'landscape' : 'portrait');
        $filename = "laporan-kas-{$tab}-".now()->format('Ymd-His').'.pdf';

        return $request->boolean('inline') ? $pdf->stream($filename) : $pdf->download($filename);
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
    public function buildBkuData(?Request $request = null, int $perPage = 25): array
    {
        $bkuTypes = [
            LedgerEntryType::PaymentConfirmed->value,
            LedgerEntryType::ExpenseApproved->value,
            LedgerEntryType::Reversal->value,
        ];

        $cashTypes = [LedgerEntryType::PaymentConfirmed->value, LedgerEntryType::ExpenseApproved->value];

        $runningBalance = 0;
        $bkuEntries = [];
        $totalDebit = 0;
        $totalCredit = 0;

        // Chunked read keeps memory flat; saldo berjalan tetap dihitung dari seluruh ledger.
        foreach (LedgerEntry::query()->whereIn('type', $bkuTypes)->lazyById(500, 'seq') as $entry) {
            $payload = json_decode($entry->payload, true) ?? [];

            if ($entry->type === LedgerEntryType::Reversal && ! in_array($payload['type'] ?? null, $cashTypes, true)) {
                continue;
            }

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
                'no' => count($bkuEntries) + 1,
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

        $paginator = null;
        if ($request) {
            $lastPage = max(1, (int) ceil(count($bkuEntries) / $perPage));
            $page = min($lastPage, max(1, $request->integer('bku_page') ?: $lastPage));
            $paginator = (new LengthAwarePaginator(
                array_slice($bkuEntries, ($page - 1) * $perPage, $perPage),
                count($bkuEntries),
                $perPage,
                $page,
                ['path' => $request->url(), 'pageName' => 'bku_page']
            ))->appends($request->except('bku_page'));
        }

        return [
            'entries' => $paginator ? $paginator->items() : $bkuEntries,
            'paginator' => $paginator,
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
    public function buildMatrixData(?Request $request = null, int $perPage = 25, int $periodLimit = 6): array
    {
        $memberQuery = User::payableMembers()
            ->orderByRaw('CAST(student_no AS UNSIGNED) ASC, student_no ASC');

        $allPeriods = BillingPeriod::with('category')->orderBy('id', 'asc')->get();
        $periodsById = $allPeriods->keyBy('id');

        // paid_sum is aggregated in SQL, so payment rows are never hydrated.
        $invoicesMap = [];
        $periodTotals = array_fill_keys($allPeriods->pluck('id')->all(), 0);
        $studentTotals = [];
        $overallTarget = 0;
        $overallPaid = 0;

        Invoice::query()
            ->withSum(['payments as paid_sum' => fn ($q) => $q->where('status', 'paid')], 'amount')
            ->whereHas('user', fn ($q) => $q->where('role', '!=', UserRole::Admin))
            ->lazyById(500)
            ->each(function (Invoice $inv) use (&$invoicesMap, &$periodTotals, &$studentTotals, &$overallTarget, &$overallPaid, $periodsById) {
                $target = (int) $inv->amount;
                $paid = $inv->status === 'paid' ? $target : min($target, (int) $inv->paid_sum);
                $inv->setRelation('period', $periodsById[$inv->billing_period_id] ?? null);

                $invoicesMap[$inv->user_id][$inv->billing_period_id] = ['invoice' => $inv, 'paid' => $paid];
                $studentTotals[$inv->user_id]['target'] = ($studentTotals[$inv->user_id]['target'] ?? 0) + $target;
                $studentTotals[$inv->user_id]['paid'] = ($studentTotals[$inv->user_id]['paid'] ?? 0) + $paid;
                $periodTotals[$inv->billing_period_id] = ($periodTotals[$inv->billing_period_id] ?? 0) + $paid;
                $overallTarget += $target;
                $overallPaid += $paid;
            });

        $paginator = null;
        $hiddenPeriods = 0;
        $periods = $allPeriods;

        if ($request) {
            $search = trim((string) $request->query('mq', ''));
            $memberQuery->when($search !== '', fn ($q) => $q->where(
                fn ($w) => $w->where('name', 'like', "%{$search}%")->orWhere('student_no', 'like', "%{$search}%")
            ));

            $paginator = $memberQuery->paginate($perPage, ['*'], 'matrix_page')->withQueryString();
            $members = $paginator->getCollection();
            $firstNo = $paginator->firstItem() ?? 1;

            if (! $request->boolean('all_periods') && $allPeriods->count() > $periodLimit) {
                $periods = $allPeriods->slice(-$periodLimit)->values();
                $hiddenPeriods = $allPeriods->count() - $periodLimit;
            }
        } else {
            $members = $memberQuery->get();
            $firstNo = 1;
        }

        $matrixRows = [];

        foreach ($members as $idx => $member) {
            $cells = [];

            foreach ($periods as $period) {
                $entry = $invoicesMap[$member->id][$period->id] ?? null;
                if (! $entry) {
                    $cells[$period->id] = ['status' => 'none', 'paid' => 0, 'target' => 0, 'remaining' => 0, 'is_overdue' => false];

                    continue;
                }

                $inv = $entry['invoice'];
                $cells[$period->id] = [
                    'status' => $inv->status,
                    'paid' => $entry['paid'],
                    'target' => (int) $inv->amount,
                    'remaining' => max(0, (int) $inv->amount - $entry['paid']),
                    'is_overdue' => $inv->status !== 'paid' && (bool) $inv->period?->due_date->endOfDay()->isPast(),
                    'invoice_id' => $inv->id,
                ];
            }

            $studentTarget = $studentTotals[$member->id]['target'] ?? 0;
            $studentPaid = $studentTotals[$member->id]['paid'] ?? 0;
            $studentRemaining = max(0, $studentTarget - $studentPaid);

            $matrixRows[] = [
                'no' => $firstNo + $idx,
                'user' => $member,
                'cells' => $cells,
                'total_target' => $studentTarget,
                'total_paid' => $studentPaid,
                'total_remaining' => $studentRemaining,
                'status' => match (true) {
                    $studentTarget > 0 && $studentRemaining === 0 => 'Lunas',
                    $studentPaid > 0 => 'Menyicil',
                    $studentTarget > 0 => 'Belum Bayar',
                    default => 'Bebas Kas',
                },
            ];
        }

        return [
            'periods' => $periods,
            'hiddenPeriods' => $hiddenPeriods,
            'paginator' => $paginator,
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

<?php

namespace App\Livewire;

use App\Http\Controllers\PaymentReportController;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Body of the payment report (stats, tabs, detail list, period summary, matrix, BKU).
 * Every filter, tab and page change re-renders in place; state stays in the URL under the same names as before.
 */
class PaymentReport extends Component
{
    use WithPagination;

    #[Url(except: 'detail')]
    public string $tab = 'detail';

    #[Url(as: 'period_id', except: '')]
    public string $periodId = '';

    #[Url(as: 'category_id', except: '')]
    public string $categoryId = '';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(as: 'rekap_per_page', except: '')]
    public string $summaryPerPage = '';

    #[Url(as: 'mq', except: '')]
    public string $matrixSearch = '';

    #[Url(as: 'm_category', except: '')]
    public string $matrixCategory = '';

    #[Url(as: 'm_cols', except: '')]
    public string $matrixColumns = '';

    #[Url(as: 'period_page', except: '')]
    public string $periodWindow = '';

    /** Matrix tab layout: '' = per-period board (default), 'grid' = classic student x period matrix. */
    #[Url(as: 'm_view', except: '')]
    public string $matrixView = '';

    /** Period shown on the per-period board ('' = newest). */
    #[Url(as: 'm_period', except: '')]
    public string $matrixPeriod = '';

    /**
     * Livewire update requests skip the route middleware, so the staff/admin gate is repeated here.
     */
    public function boot(): void
    {
        abort_unless(auth()->user()?->canManageMembers(), 403);
    }

    /**
     * BKU paging is read from the request (it defaults to the last page), so seed it from the URL on first load.
     */
    public function mount(): void
    {
        if (request()->integer('bku_page') > 0) {
            $this->setPage(request()->integer('bku_page'), 'bku_page');
        }
    }

    public function showTab(string $tab): void
    {
        $this->tab = in_array($tab, ['detail', 'matrix', 'bku'], true) ? $tab : 'detail';
    }

    public function filterStatus(string $status): void
    {
        $this->status = $status;
        $this->resetPage('invoice_page');
    }

    public function showPeriodWindow(int $page): void
    {
        $this->periodWindow = $page > 1 ? (string) $page : '';
    }

    public function showMatrixView(string $view): void
    {
        $this->matrixView = $view === 'grid' ? 'grid' : '';
    }

    public function showMatrixPeriod(int $periodId): void
    {
        $this->matrixPeriod = $periodId > 0 ? (string) $periodId : '';
    }

    public function resetMatrixFilters(): void
    {
        $this->reset('matrixSearch', 'matrixCategory', 'matrixColumns', 'periodWindow', 'matrixPeriod');
        $this->resetPage('matrix_page');
    }

    /**
     * A changed filter invalidates the page/window the user was on.
     */
    public function updated(string $property): void
    {
        if (in_array($property, ['periodId', 'categoryId', 'search'], true)) {
            $this->resetPage('invoice_page');
            $this->resetPage('rekap_page');
        }

        if ($property === 'summaryPerPage') {
            $this->resetPage('rekap_page');
        }

        if (in_array($property, ['matrixSearch', 'matrixCategory', 'matrixColumns'], true)) {
            $this->resetPage('matrix_page');
            $this->periodWindow = '';
        }

        if ($property === 'matrixCategory') {
            $this->matrixPeriod = '';
        }
    }

    /**
     * Current state as URL query parameters (also used for PDF/CSV links and no-JS fallbacks).
     *
     * @return array<string, string>
     */
    public function query(): array
    {
        return array_filter([
            'tab' => $this->tab === 'detail' ? '' : $this->tab,
            'period_id' => $this->periodId,
            'category_id' => $this->categoryId,
            'q' => trim($this->search),
            'status' => $this->status,
            'rekap_per_page' => $this->summaryPerPage,
            'mq' => trim($this->matrixSearch),
            'm_category' => $this->matrixCategory,
            'm_cols' => $this->matrixColumns,
            'period_page' => $this->periodWindow,
            'm_view' => $this->matrixView,
            'm_period' => $this->matrixPeriod,
        ], fn (string $value) => $value !== '');
    }

    public function render(PaymentReportController $reports): View
    {
        $pages = array_map('strval', $this->paginators);
        $request = Request::create(route('reports.payments'), 'GET', $this->query() + $pages);
        $request->setUserResolver(fn () => auth()->user());

        return view('livewire.payment-report', [
            ...$reports->reportData($request),
            'query' => $this->query(),
        ]);
    }
}

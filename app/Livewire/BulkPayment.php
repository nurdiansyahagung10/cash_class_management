<?php

namespace App\Livewire;

use App\Models\Invoice;
use App\Services\QrisService;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * "Bayar sekaligus": ticking a bill recalculates the total and the dynamic QRIS in place (no "Hitung total" click).
 * The proof itself is still sent as a normal multipart POST to payments.bulk.submit.
 */
class BulkPayment extends Component
{
    #[Locked]
    public int $invoiceId;

    /**
     * Selected invoice ids (strings, as checkbox values); mirrored in the URL as ?ids[]=.
     *
     * @var list<string>
     */
    #[Url(as: 'ids')]
    public array $selected = [];

    public function mount(Invoice $invoice): void
    {
        $this->invoiceId = $invoice->id;
        $this->authorizeAccess();

        if ($this->selected === []) {
            $this->selected = [(string) $invoice->id];
        }
    }

    /**
     * Same rule as the page route; repeated because Livewire updates skip route middleware.
     */
    public function hydrate(): void
    {
        $this->authorizeAccess();
    }

    public function selectAll(): void
    {
        $this->selected = $this->payable()->pluck('id')->map(fn (int $id) => (string) $id)->all();
    }

    public function clearSelection(): void
    {
        $this->selected = [];
    }

    public function render(QrisService $qris): View
    {
        $anchor = $this->anchor();
        $invoices = $this->payable();
        $selected = $invoices->whereIn('id', array_map('intval', $this->selected))->values();
        $total = (int) $selected->sum(fn (Invoice $item) => $item->remainingAmount());
        $qrisSource = $selected->map(fn (Invoice $item) => $item->period->qris_url)->filter()->last();

        return view('livewire.bulk-payment', [
            'anchor' => $anchor,
            'invoices' => $invoices,
            'selectedIds' => $selected->pluck('id')->all(),
            'total' => $total,
            'qris' => $total > 0 && $qrisSource ? $qris->imageFor($qrisSource, $total) : null,
        ]);
    }

    private function anchor(): Invoice
    {
        return Invoice::with('user')->findOrFail($this->invoiceId);
    }

    /**
     * @return Collection<int, Invoice>
     */
    private function payable()
    {
        return Invoice::payableFor($this->anchor()->user);
    }

    private function authorizeAccess(): void
    {
        $invoice = $this->anchor();
        $user = auth()->user();

        abort_if($user === null || $invoice->user->isAdmin(), 403);
        abort_unless($invoice->user_id === $user->id || $user->isStaff(), 403);
    }
}

<?php

use App\Models\Invoice;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Invoices whose payments are confirmed but whose cached status says otherwise show up as "belum bayar" in reports.
     */
    public function up(): void
    {
        Invoice::repairStatuses();
    }

    public function down(): void
    {
        // Data repair only; nothing to undo.
    }
};

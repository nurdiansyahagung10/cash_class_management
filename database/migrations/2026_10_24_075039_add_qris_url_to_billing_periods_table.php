<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_periods', function (Blueprint $table): void {
            $table->string('qris_url')->nullable()->after('due_date');
        });
    }

    public function down(): void
    {
        Schema::table('billing_periods', function (Blueprint $table): void {
            $table->dropColumn('qris_url');
        });
    }
};

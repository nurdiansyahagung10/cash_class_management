<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_periods', function (Blueprint $table): void {
            $table->text('qris_url')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('billing_periods', function (Blueprint $table): void {
            $table->string('qris_url', 255)->nullable()->change();
        });
    }
};

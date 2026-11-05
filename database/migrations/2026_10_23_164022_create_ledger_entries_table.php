<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id('seq');
            $table->string('type');
            $table->string('ref_table')->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->text('payload');
            $table->foreignId('actor_id')->nullable()->constrained('users');
            $table->timestamp('created_at');
            $table->char('prev_hash', 64)->nullable();
            $table->char('hash', 64)->unique();
        });

        foreach (['UPDATE', 'DELETE'] as $operation) {
            $name = 'ledger_entries_no_'.strtolower($operation);
            $message = 'ledger_entries is append-only';

            DB::unprepared(match (DB::getDriverName()) {
                'sqlite' => "CREATE TRIGGER {$name} BEFORE {$operation} ON ledger_entries BEGIN SELECT RAISE(ABORT, '{$message}'); END",
                default => "CREATE TRIGGER {$name} BEFORE {$operation} ON ledger_entries FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '{$message}'",
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};

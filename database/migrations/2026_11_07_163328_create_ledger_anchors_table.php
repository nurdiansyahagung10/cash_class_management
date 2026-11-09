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
        Schema::create('ledger_anchors', function (Blueprint $table) {
            $table->id();
            $table->date('anchored_on')->unique();
            $table->unsignedBigInteger('seq');
            $table->char('hash', 64);
            $table->unsignedInteger('entry_count');
            $table->timestamp('created_at');
        });

        foreach (['UPDATE', 'DELETE'] as $operation) {
            $name = 'ledger_anchors_no_'.strtolower($operation);
            $message = 'ledger_anchors is append-only';

            DB::unprepared(match (DB::getDriverName()) {
                'sqlite' => "CREATE TRIGGER {$name} BEFORE {$operation} ON ledger_anchors BEGIN SELECT RAISE(ABORT, '{$message}'); END",
                default => "CREATE TRIGGER {$name} BEFORE {$operation} ON ledger_anchors FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '{$message}'",
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledger_anchors');
    }
};

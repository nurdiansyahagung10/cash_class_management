<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insert default categories
        $defaults = [
            ['name' => 'Uang Kas', 'description' => 'Iuran rutin kas kelas mingguan atau bulanan'],
            ['name' => 'Kegiatan & Acara', 'description' => 'Dana untuk acara kelas, study tour, makrab, dan perlombaan'],
            ['name' => 'Baju & Merchandise', 'description' => 'Pembayaran jaket angkatan, kaos kelas, dan atribut'],
            ['name' => 'Buku & Praktikum', 'description' => 'Biaya modul, diktat, fotokopi materi, dan alat kuliah'],
            ['name' => 'Sosial & Duka', 'description' => 'Sumbangan sosial, kas duka cita, dan jenguk teman sakit'],
        ];

        $now = now();
        foreach ($defaults as $cat) {
            DB::table('categories')->insert([
                'name' => $cat['name'],
                'description' => $cat['description'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $defaultCategoryId = DB::table('categories')->where('name', 'Uang Kas')->value('id');

        Schema::table('billing_periods', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('id')->constrained('categories')->nullOnDelete();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('invoice_id')->constrained('categories')->nullOnDelete();
        });

        // Backfill existing billing periods and payments with default category
        if ($defaultCategoryId) {
            DB::table('billing_periods')->whereNull('category_id')->update(['category_id' => $defaultCategoryId]);
            DB::table('payments')->whereNull('category_id')->update(['category_id' => $defaultCategoryId]);
        }
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });

        Schema::table('billing_periods', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });

        Schema::dropIfExists('categories');
    }
};

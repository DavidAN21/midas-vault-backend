<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Untuk MySQL
        Schema::table('trade_ins', function (Blueprint $table) {
            $table->enum('status', ['pending', 'accepted', 'rejected', 'completed', 'cancelled'])->default('pending')->change();
        });

        // Untuk SQLite atau database lain yang tidak support change enum
        // Kita buat backup approach
        try {
            Schema::table('trade_ins', function (Blueprint $table) {
                $table->enum('status', ['pending', 'accepted', 'rejected', 'completed', 'cancelled'])->default('pending')->change();
            });
        } catch (\Exception $e) {
            // Jika tidak bisa alter column, buat column baru
            Schema::table('trade_ins', function (Blueprint $table) {
                $table->enum('status_new', ['pending', 'accepted', 'rejected', 'completed', 'cancelled'])->default('pending')->after('status');
            });
            
            // Copy data
            DB::statement('UPDATE trade_ins SET status_new = status');
            
            // Drop old column dan rename new column
            Schema::table('trade_ins', function (Blueprint $table) {
                $table->dropColumn('status');
                $table->renameColumn('status_new', 'status');
            });
        }
    }

    public function down(): void
    {
        Schema::table('trade_ins', function (Blueprint $table) {
            $table->string('status')->default('pending')->change();
        });
    }
};
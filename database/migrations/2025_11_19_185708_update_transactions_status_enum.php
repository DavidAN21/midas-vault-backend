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
        DB::statement("ALTER TABLE transactions MODIFY COLUMN status ENUM('pending', 'escrow', 'completed', 'cancelled', 'refunded') DEFAULT 'pending'");
        
        // Untuk lainnya, gunakan schema
        // Schema::table('transactions', function (Blueprint $table) {
        //     $table->enum('status', [
        //         'pending', 
        //         'escrow', 
        //         'completed', 
        //         'cancelled',
        //         'refunded'
        //     ])->default('pending')->change();
        // });
    }

    public function down(): void
    {
        // Kembalikan ke sebelumnya jika perlu
        DB::statement("ALTER TABLE transactions MODIFY COLUMN status ENUM('pending', 'escrow', 'completed', 'refunded') DEFAULT 'pending'");
    }
};
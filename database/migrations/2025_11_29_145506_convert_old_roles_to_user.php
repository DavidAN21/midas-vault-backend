<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ubah semua buyer & seller menjadi user dulu
        DB::table('users')
            ->whereIn('role', ['buyer', 'seller'])
            ->update(['role' => 'user']);

        // 2. Setelah aman, baru ubah enum
        DB::statement("ALTER TABLE users MODIFY role ENUM('user', 'admin') DEFAULT 'user'");
    }

    public function down(): void
    {
        // Balikin enum ke awal (jika rollback)
        DB::statement("ALTER TABLE users MODIFY role ENUM('buyer', 'seller', 'admin') DEFAULT 'buyer'");
    }

};

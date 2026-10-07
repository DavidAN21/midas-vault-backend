<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Hapus foreign key dari tabel reviews jika ada
        if (Schema::hasTable('reviews')) {
            Schema::table('reviews', function (Blueprint $table) {
                // Cek kolom trade_in_id ada atau tidak
                if (Schema::hasColumn('reviews', 'trade_in_id')) {
                    $table->dropForeign(['trade_in_id']); // drop FK
                    // OPTIONAL: jika kamu ingin drop kolom juga
                    // $table->dropColumn('trade_in_id');
                }
            });
        }

        // 2. Baru drop tabel trade_ins
        Schema::dropIfExists('trade_ins');
        
        // 3. Buat ulang table dengan struktur baru
        Schema::create('trade_ins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('old_product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('new_product_id')->constrained('products')->onDelete('cascade');
            $table->decimal('price_difference', 10, 2)->default(0);
            $table->enum('payment_status', ['pending', 'paid', 'refunded'])->default('pending');
            $table->enum('status', ['pending', 'accepted', 'rejected', 'completed', 'cancelled'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trade_ins');
    }
};

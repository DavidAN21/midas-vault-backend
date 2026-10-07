<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Untuk fitur barter
            $table->boolean('allow_barter')->default(false);
            $table->text('barter_preferences')->nullable();
            
            // Untuk fitur tukar tambah
            $table->boolean('allow_trade_in')->default(false);
            $table->decimal('trade_in_value', 10, 2)->nullable();
            $table->text('trade_in_preferences')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'allow_barter',
                'barter_preferences',
                'allow_trade_in', 
                'trade_in_value',
                'trade_in_preferences'
            ]);
        });
    }
};
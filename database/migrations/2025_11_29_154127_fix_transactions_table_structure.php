<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            // 🔥 OPTION 1: Jika field amount belum ada, tambahkan
            if (!Schema::hasColumn('transactions', 'amount')) {
                $table->decimal('amount', 10, 2)->default(0);
            }
            
            // 🔥 OPTION 2: Jika field ada tapi tidak ada default, ubah
            // $table->decimal('amount', 10, 2)->default(0)->change();
            
            // 🔥 OPTION 3: Tambah field yang mungkin missing
            $fieldsToAdd = [
                'payment_method' => ['string', 'nullable'],
                'payment_reference' => ['string', 'nullable'],
                'escrow_status' => ['string', 'default' => 'pending']
            ];
            
            foreach ($fieldsToAdd as $field => $config) {
                if (!Schema::hasColumn('transactions', $field)) {
                    if ($config[0] === 'string') {
                        $table->string($field)->nullable();
                    }
                }
            }
        });
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Rollback logic jika perlu
        });
    }
};
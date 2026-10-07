<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barters', function (Blueprint $table) {
            $table->boolean('requester_confirmed')->default(false);
            $table->boolean('receiver_confirmed')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('barters', function (Blueprint $table) {
            $table->dropColumn(['requester_confirmed', 'receiver_confirmed']);
        });
    }
};
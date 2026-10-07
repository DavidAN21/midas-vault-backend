<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('barters', function (Blueprint $table) {
            $table->text('message')->nullable()->after('notes');
        });
    }

    public function down()
    {
        Schema::table('barters', function (Blueprint $table) {
            $table->dropColumn('message');
        });
    }
};
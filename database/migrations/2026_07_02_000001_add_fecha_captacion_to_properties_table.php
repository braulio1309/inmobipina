<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->date('fecha_captacion')->nullable()->after('approved_by');
            $table->index('fecha_captacion');
        });
    }

    public function down()
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropIndex(['fecha_captacion']);
            $table->dropColumn('fecha_captacion');
        });
    }
};

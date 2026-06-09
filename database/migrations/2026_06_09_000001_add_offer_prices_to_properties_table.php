<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->decimal('sale_price', 12, 2)->nullable()->after('price');
            $table->decimal('rental_price', 12, 2)->nullable()->after('sale_price');
        });
    }

    public function down()
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn(['sale_price', 'rental_price']);
        });
    }
};
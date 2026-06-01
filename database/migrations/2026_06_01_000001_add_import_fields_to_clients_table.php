<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('location')->nullable()->after('phone');
            $table->string('tipo_inmueble')->nullable()->after('location');
            $table->string('tipo_neg')->nullable()->after('tipo_inmueble');
        });
    }

    public function down()
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['location', 'tipo_inmueble', 'tipo_neg']);
        });
    }
};

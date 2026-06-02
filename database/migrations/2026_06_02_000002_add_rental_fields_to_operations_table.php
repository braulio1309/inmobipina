<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('operations', function (Blueprint $table) {
            $table->unsignedInteger('meses_adelanto')->default(0)->after('fecha_cierre');
            $table->unsignedInteger('mes_administrativo')->default(0)->after('meses_adelanto');
            $table->date('fecha_corte')->nullable()->after('mes_administrativo');
        });
    }

    public function down()
    {
        Schema::table('operations', function (Blueprint $table) {
            $table->dropColumn(['meses_adelanto', 'mes_administrativo', 'fecha_corte']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('exclusivities', function (Blueprint $table) {
            $table->string('propietario_nombre')->nullable();
            $table->string('propietario_ci')->nullable();
            $table->string('propietario_rif')->nullable();
            $table->string('propietario_email')->nullable();
            $table->string('propietario_telefono')->nullable();
            $table->string('inmueble_descripcion')->nullable();
            $table->string('parroquia')->nullable()->default('Cachamay');
            $table->string('registro_numero')->nullable();
            $table->string('registro_folio')->nullable();
            $table->string('registro_tomo')->nullable();
            $table->string('registro_protocolo')->nullable();
            $table->string('registro_anio')->nullable();
            $table->date('registro_fecha')->nullable();
            $table->decimal('precio_venta', 12, 2)->nullable();
            $table->date('fecha_firma')->nullable();
            $table->string('contract_path')->nullable();
        });
    }

    public function down()
    {
        Schema::table('exclusivities', function (Blueprint $table) {
            $table->dropColumn([
                'propietario_nombre', 'propietario_ci', 'propietario_rif',
                'propietario_email', 'propietario_telefono', 'inmueble_descripcion',
                'parroquia', 'registro_numero', 'registro_folio', 'registro_tomo',
                'registro_protocolo', 'registro_anio', 'registro_fecha',
                'precio_venta', 'fecha_firma', 'contract_path',
            ]);
        });
    }
};

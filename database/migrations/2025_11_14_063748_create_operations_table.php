<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       Schema::create('operations', function (Blueprint $table) {
            $table->id();

            // venta, reserva, exclusividad
            $table->enum('type', ['venta', 'reserva', 'exclusividad']);

            // propiedad
            $table->foreignId('property_id')->constrained('properties')->onDelete('cascade');

            // monto total
            $table->decimal('amount', 12, 2)->nullable();

            // fechas (start y end solo aplica para exclusividad)
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();

            // notas
            $table->text('notes')->nullable();

            $table->timestamps();
        });

        // tabla pivote compradores
        Schema::create('operation_client', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operation_id')->constrained('operations')->onDelete('cascade');
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
        });

        // tabla pivote vendedores (users)
        Schema::create('operation_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operation_id')->constrained('operations')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('operations');
    }
};

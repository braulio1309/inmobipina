<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('property_captations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('fotos_descargadas')->nullable();
            $table->boolean('enviado_para_flyer')->nullable();
            $table->string('codigo_publicacion')->nullable();
            $table->boolean('recepcion_documentos_correo')->nullable();
            $table->date('fecha_captacion')->nullable();
            $table->string('asesor_responsable')->nullable();
            $table->string('tipo_inmueble')->nullable();
            $table->decimal('precio_inmobiliaria', 12, 2)->nullable();
            $table->decimal('precio_cliente', 12, 2)->nullable();
            $table->decimal('porcentaje_comision', 5, 2)->nullable();
            $table->string('tipo_negociacion')->nullable();
            $table->string('cliente_nombre_apellido')->nullable();
            $table->string('cliente_nro_contacto')->nullable();
            $table->boolean('cliente_es_propietario')->nullable();
            $table->boolean('cliente_es_apoderado')->nullable();
            $table->boolean('cliente_es_encargado')->nullable();
            $table->string('cliente_correo_electronico')->nullable();
            $table->string('tipo_cliente')->nullable();
            $table->text('ubicacion')->nullable();
            $table->text('punto_referencia')->nullable();
            $table->decimal('m2_terreno', 12, 2)->nullable();
            $table->decimal('m2_construccion', 12, 2)->nullable();
            $table->decimal('precio_m2', 12, 2)->nullable();
            $table->string('cantidad_habitaciones')->nullable();
            $table->string('cantidad_banos')->nullable();
            $table->string('nivel_piso')->nullable();
            $table->string('cocina')->nullable();
            $table->string('telefono')->nullable();
            $table->string('tipo_internet')->nullable();
            $table->string('capacidad_estacionamiento')->nullable();
            $table->string('closet')->nullable();
            $table->string('estudio')->nullable();
            $table->string('acabados')->nullable();
            $table->boolean('listo_para_habitar')->nullable();
            $table->boolean('para_remodelar')->nullable();
            $table->date('fecha_verificacion')->nullable();
            $table->string('documentacion_estado')->nullable();
            $table->text('datos_registro')->nullable();
            $table->boolean('hipoteca')->nullable();
            $table->string('banco')->nullable();
            $table->string('estado_tramite')->nullable();
            $table->text('ventajas_beneficios')->nullable();
            $table->text('observaciones_recomendaciones')->nullable();
            $table->string('autorizacion_nombre')->nullable();
            $table->string('autorizacion_nacionalidad')->nullable();
            $table->string('autorizacion_cedula')->nullable();
            $table->string('autorizacion_caracter')->nullable();
            $table->boolean('autoriza_venta')->nullable();
            $table->boolean('autoriza_alquiler')->nullable();
            $table->text('autorizacion_inmueble_constituido')->nullable();
            $table->text('autorizacion_ubicado_en')->nullable();
            $table->string('autorizacion_precio')->nullable();
            $table->string('autorizacion_comision')->nullable();
            $table->boolean('medio_instagram_facebook')->nullable();
            $table->boolean('medio_pendon_sticker')->nullable();
            $table->boolean('medio_video_publicitario')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamps();

            $table->unique('property_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('property_captations');
    }
};
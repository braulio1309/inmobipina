<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_captations', function (Blueprint $table) {
            if (!Schema::hasColumn('property_captations', 'es_otra_inmobiliaria')) {
                $table->boolean('es_otra_inmobiliaria')->nullable()->after('tipo_negociacion');
            }

            if (!Schema::hasColumn('property_captations', 'nombre_inmobiliaria')) {
                $table->string('nombre_inmobiliaria')->nullable()->after('es_otra_inmobiliaria');
            }

            if (!Schema::hasColumn('property_captations', 'numero_contacto')) {
                $table->string('numero_contacto')->nullable()->after('nombre_inmobiliaria');
            }
        });
    }

    public function down(): void
    {
        Schema::table('property_captations', function (Blueprint $table) {
            if (Schema::hasColumn('property_captations', 'numero_contacto')) {
                $table->dropColumn('numero_contacto');
            }

            if (Schema::hasColumn('property_captations', 'nombre_inmobiliaria')) {
                $table->dropColumn('nombre_inmobiliaria');
            }

            if (Schema::hasColumn('property_captations', 'es_otra_inmobiliaria')) {
                $table->dropColumn('es_otra_inmobiliaria');
            }
        });
    }
};

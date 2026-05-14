<?php

namespace App\Models;

use App\Models\Core\Auth\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyCaptation extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'user_id',
        'fotos_descargadas',
        'enviado_para_flyer',
        'codigo_publicacion',
        'recepcion_documentos_correo',
        'fecha_captacion',
        'asesor_responsable',
        'tipo_inmueble',
        'precio_inmobiliaria',
        'precio_cliente',
        'porcentaje_comision',
        'tipo_negociacion',
        'cliente_nombre_apellido',
        'cliente_nro_contacto',
        'cliente_es_propietario',
        'cliente_es_apoderado',
        'cliente_es_encargado',
        'cliente_correo_electronico',
        'tipo_cliente',
        'ubicacion',
        'punto_referencia',
        'm2_terreno',
        'm2_construccion',
        'precio_m2',
        'cantidad_habitaciones',
        'cantidad_banos',
        'nivel_piso',
        'cocina',
        'telefono',
        'tipo_internet',
        'capacidad_estacionamiento',
        'closet',
        'estudio',
        'acabados',
        'listo_para_habitar',
        'para_remodelar',
        'fecha_verificacion',
        'documentacion_estado',
        'datos_registro',
        'hipoteca',
        'banco',
        'estado_tramite',
        'ventajas_beneficios',
        'observaciones_recomendaciones',
        'autorizacion_nombre',
        'autorizacion_nacionalidad',
        'autorizacion_cedula',
        'autorizacion_caracter',
        'autoriza_venta',
        'autoriza_alquiler',
        'autorizacion_inmueble_constituido',
        'autorizacion_ubicado_en',
        'autorizacion_precio',
        'autorizacion_comision',
        'medio_instagram_facebook',
        'medio_pendon_sticker',
        'medio_video_publicitario',
        'pdf_path',
    ];

    protected $casts = [
        'fotos_descargadas' => 'boolean',
        'enviado_para_flyer' => 'boolean',
        'recepcion_documentos_correo' => 'boolean',
        'fecha_captacion' => 'date',
        'cliente_es_propietario' => 'boolean',
        'cliente_es_apoderado' => 'boolean',
        'cliente_es_encargado' => 'boolean',
        'm2_terreno' => 'decimal:2',
        'm2_construccion' => 'decimal:2',
        'precio_inmobiliaria' => 'decimal:2',
        'precio_cliente' => 'decimal:2',
        'porcentaje_comision' => 'decimal:2',
        'precio_m2' => 'decimal:2',
        'listo_para_habitar' => 'boolean',
        'para_remodelar' => 'boolean',
        'fecha_verificacion' => 'date',
        'hipoteca' => 'boolean',
        'autoriza_venta' => 'boolean',
        'autoriza_alquiler' => 'boolean',
        'medio_instagram_facebook' => 'boolean',
        'medio_pendon_sticker' => 'boolean',
        'medio_video_publicitario' => 'boolean',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
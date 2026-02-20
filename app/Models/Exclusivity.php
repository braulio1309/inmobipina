<?php

namespace App\Models;

use App\Models\Core\Auth\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exclusivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id', 'user_id', 'start_date', 'end_date', 'file_path',
        'propietario_nombre', 'propietario_ci', 'propietario_rif',
        'propietario_email', 'propietario_telefono', 'inmueble_descripcion',
        'parroquia', 'registro_numero', 'registro_folio', 'registro_tomo',
        'registro_protocolo', 'registro_anio', 'registro_fecha',
        'precio_venta', 'fecha_firma', 'contract_path',
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

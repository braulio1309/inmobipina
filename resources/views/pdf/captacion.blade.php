<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Formulario de Captacion</title>
    <style>
        @page {
            margin: 22px 28px;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #222;
            margin: 0;
        }

        .page + .page {
            page-break-before: always;
        }

        .header-table,
        .grid,
        .mini-grid,
        .platform-grid {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: top;
        }

        .brand {
            width: 170px;
        }

        .brand img {
            width: 150px;
        }

        .right-brand {
            text-align: right;
            font-size: 9px;
            line-height: 1.2;
            font-weight: bold;
        }

        .top-checks {
            margin-top: 4px;
            font-size: 8px;
        }

        .top-checks span {
            margin-right: 14px;
        }

        .date-line {
            text-align: right;
            font-size: 11px;
            margin: 8px 0 6px;
        }

        .title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            text-decoration: underline;
            margin: 8px 0 14px;
        }

        .summary-table {
            width: 100%;
            margin-bottom: 12px;
        }

        .summary-table td {
            width: 50%;
            padding: 4px 0;
            font-size: 11px;
            font-weight: bold;
        }

        .line-value {
            display: inline-block;
            min-width: 140px;
            border-bottom: 1px solid #444;
            padding: 0 4px 2px;
            font-weight: normal;
        }

        .grid td,
        .grid th,
        .mini-grid td,
        .mini-grid th,
        .platform-grid td,
        .platform-grid th {
            border: 1px solid #7f7f7f;
            padding: 5px 6px;
            vertical-align: middle;
        }

        .section-title {
            font-weight: bold;
            text-align: center;
            background: #f2f2f2;
        }

        .label {
            font-weight: bold;
            white-space: nowrap;
        }

        .value {
            min-height: 14px;
        }

        .small {
            font-size: 9px;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .spacer {
            height: 12px;
        }

        .lined-block {
            margin-top: 8px;
        }

        .lined-block .heading {
            font-size: 11px;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 6px;
        }

        .line {
            border-bottom: 1px solid #7f7f7f;
            height: 18px;
            margin-bottom: 6px;
        }

        .authorization-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin: 28px 0 18px;
        }

        .paragraph {
            font-size: 11px;
            line-height: 1.7;
            text-align: justify;
        }

        .platform-grid {
            width: 44%;
            margin: 18px auto 48px;
        }

        .signature {
            width: 220px;
            margin-top: 58px;
            border-top: 1px solid #444;
            text-align: center;
            font-weight: bold;
            padding-top: 6px;
        }
    </style>
</head>
<body>
    @php
        $formatText = function ($value, $fallback = '') {
            if ($value === null) {
                return $fallback;
            }

            $text = trim((string) $value);

            return $text !== '' ? $text : $fallback;
        };

        $formatMoney = function ($value) {
            return $value !== null && $value !== '' ? number_format((float) $value, 2, ',', '.') : '';
        };

        $mark = function ($value) {
            return $value ? '[X]' : '[ ]';
        };

        $markNullable = function ($value, $target) {
            if ($value === null || $value === '') {
                return '[ ]';
            }

            return (bool) $value === $target ? '[X]' : '[ ]';
        };

        $creatorName = trim(($property->creator->first_name ?? '') . ' ' . ($property->creator->last_name ?? ''));
        $fechaCaptacion = $captation->fecha_captacion ? \Carbon\Carbon::parse($captation->fecha_captacion) : now();
        $monthName = $fechaCaptacion->locale('es')->isoFormat('MMMM');
        $advisorName = $formatText($captation->asesor_responsable, $creatorName);
        $tipoInmueble = $formatText($captation->tipo_inmueble, $property->type);
        $tipoNegociacion = $formatText($captation->tipo_negociacion, $property->type_sale);
        $precioCliente = $captation->precio_cliente ?? $property->price;
        $precioM2 = $captation->precio_m2;

        if (($precioM2 === null || $precioM2 === '') && $property->price && $property->square_meters) {
            $precioM2 = (float) $property->price / (float) $property->square_meters;
        }

        $m2Construccion = $captation->m2_construccion ?? $property->square_meters;
        $cantidadHabitaciones = $formatText($captation->cantidad_habitaciones, $property->bedrooms);
        $cantidadBanos = $formatText($captation->cantidad_banos, $property->bathrooms);
        $capacidadEstacionamiento = $formatText($captation->capacidad_estacionamiento, $property->parking_spots);
        $ubicacion = $formatText($captation->ubicacion, $property->address);
        $autorizacionNombre = $formatText($captation->autorizacion_nombre, $captation->cliente_nombre_apellido);
        $autorizacionPrecio = $formatText($captation->autorizacion_precio, $formatMoney($precioCliente) !== '' ? $formatMoney($precioCliente) . ' USD' : '');
        $autorizacionConstituido = $formatText($captation->autorizacion_inmueble_constituido, $property->description);
        $autorizacionUbicacion = $formatText($captation->autorizacion_ubicado_en, $ubicacion);
    @endphp

    <div class="page">
        <table class="header-table">
            <tr>
                <td class="brand">
                    <img src="{{ public_path('images/logo.png') }}" alt="Inmobipina">
                    <div class="top-checks">
                        <span>{{ $markNullable($captation->fotos_descargadas, true) }} FOTOS DESCARGADAS</span>
                        <span>{{ $markNullable($captation->enviado_para_flyer, true) }} ENVIADO PARA FLYER</span>
                        <span>CODIGO DE PUBLICACION: {{ $formatText($captation->codigo_publicacion) }}</span>
                        <span>{{ $markNullable($captation->recepcion_documentos_correo, true) }} RECEPCION DE DOCUMENTOS EN CORREO</span>
                    </div>
                </td>
                <td class="right-brand">
                    Camara Inmobiliaria<br>de Venezuela<br>Nro. 84
                </td>
            </tr>
        </table>

        <div class="date-line">Puerto Ordaz, {{ $fechaCaptacion->format('d') }} de {{ $monthName }} del {{ $fechaCaptacion->format('Y') }}</div>
        <div class="title">CAPTACION DE BIEN INMUEBLE</div>

        <table class="summary-table">
            <tr>
                <td>ASESOR (A) RESPONSABLE: <span class="line-value">{{ $advisorName }}</span></td>
                <td>PRECIO DE INMOBILIARIA: <span class="line-value">{{ $formatMoney($captation->precio_inmobiliaria) }}</span></td>
            </tr>
            <tr>
                <td>TIPO DE INMUEBLE: <span class="line-value">{{ $tipoInmueble }}</span></td>
                <td>PRECIO DE CLIENTE: <span class="line-value">{{ $formatMoney($precioCliente) }}</span></td>
            </tr>
            <tr>
                <td>TIPO DE NEGOCIACION: <span class="line-value">{{ $tipoNegociacion }}</span></td>
                <td>% COMISION: <span class="line-value">{{ $formatText($captation->porcentaje_comision) }}</span></td>
            </tr>
        </table>

        <table class="grid">
            <tr>
                <th colspan="6" class="section-title">INFORMACION DEL CLIENTE</th>
            </tr>
            <tr>
                <td colspan="3"><span class="label">NOMBRE Y APELLIDO:</span> <span class="value">{{ $formatText($captation->cliente_nombre_apellido) }}</span></td>
                <td colspan="3"><span class="label">NRO. DE CONTACTO:</span> <span class="value">{{ $formatText($captation->cliente_nro_contacto) }}</span></td>
            </tr>
            <tr>
                <td class="small text-center">{{ $mark($captation->cliente_es_propietario) }} PROPIETARIO</td>
                <td class="small text-center">{{ $mark($captation->cliente_es_apoderado) }} APODERADO</td>
                <td class="small text-center">{{ $mark($captation->cliente_es_encargado) }} ENCARGADO</td>
                <td colspan="3"><span class="label">CORREO ELECTRONICO:</span> <span class="value">{{ $formatText($captation->cliente_correo_electronico) }}</span></td>
            </tr>
            <tr>
                <td colspan="6"><span class="label">TIPO DE CLIENTE (ASIGNADO/REFERIDO):</span> <span class="value">{{ $formatText($captation->tipo_cliente) }}</span></td>
            </tr>

            <tr>
                <th colspan="6" class="section-title">CARACTERISTICAS DEL BIEN INMUEBLE</th>
            </tr>
            <tr>
                <td colspan="6"><span class="label">UBICACION:</span> <span class="value">{{ $ubicacion }}</span></td>
            </tr>
            <tr>
                <td colspan="6"><span class="label">PUNTO DE REFERENCIA:</span> <span class="value">{{ $formatText($captation->punto_referencia) }}</span></td>
            </tr>
            <tr>
                <td colspan="2"><span class="label">m2 TERRENO:</span> <span class="value">{{ $formatText($captation->m2_terreno) }}</span></td>
                <td colspan="2"><span class="label">m2 CONSTRUCCION:</span> <span class="value">{{ $formatText($m2Construccion) }}</span></td>
                <td colspan="2"><span class="label">PRECIO X m2:</span> <span class="value">{{ $formatMoney($precioM2) }}</span></td>
            </tr>
            <tr>
                <td colspan="2"><span class="label">CANT. DE HAB:</span> <span class="value">{{ $cantidadHabitaciones }}</span></td>
                <td colspan="2"><span class="label">CANT. DE BANO:</span> <span class="value">{{ $cantidadBanos }}</span></td>
                <td colspan="2"><span class="label">NIVEL/PISO:</span> <span class="value">{{ $formatText($captation->nivel_piso) }}</span></td>
            </tr>
            <tr>
                <td colspan="2"><span class="label">COCINA:</span> <span class="value">{{ $formatText($captation->cocina) }}</span></td>
                <td colspan="2"><span class="label">TELEFONO:</span> <span class="value">{{ $formatText($captation->telefono) }}</span></td>
                <td colspan="2"><span class="label">TIPO DE INTERNET:</span> <span class="value">{{ $formatText($captation->tipo_internet) }}</span></td>
            </tr>
            <tr>
                <td colspan="3"><span class="label">CAPACIDAD DE ESTACIONAMIENTO:</span> <span class="value">{{ $capacidadEstacionamiento }}</span></td>
                <td colspan="2"><span class="label">CLOSET:</span> <span class="value">{{ $formatText($captation->closet) }}</span></td>
                <td><span class="label">ESTUDIO:</span> <span class="value">{{ $formatText($captation->estudio) }}</span></td>
            </tr>
            <tr>
                <td colspan="2"><span class="label">ACABADOS:</span> <span class="value">{{ $formatText($captation->acabados) }}</span></td>
                <td colspan="2"><span class="label">LISTO P/HABITAR:</span> <span class="value">{{ $markNullable($captation->listo_para_habitar, true) }}</span></td>
                <td colspan="2"><span class="label">P/REMODELAR:</span> <span class="value">{{ $markNullable($captation->para_remodelar, true) }}</span></td>
            </tr>

            <tr>
                <th colspan="6" class="section-title">DOCUMENTACION</th>
            </tr>
            <tr>
                <td colspan="2"><span class="label">FECHA DE VERIFICACION:</span> <span class="value">{{ $captation->fecha_verificacion ? \Carbon\Carbon::parse($captation->fecha_verificacion)->format('d/m/Y') : '' }}</span></td>
                <td colspan="4"><span class="label">ESTADO:</span> <span class="value">{{ $formatText($captation->documentacion_estado) }}</span></td>
            </tr>
            <tr>
                <td colspan="6"><span class="label">DATOS DE REGISTRO:</span> <span class="value">{{ $formatText($captation->datos_registro) }}</span></td>
            </tr>
            <tr>
                <td><span class="label">HIPOTECA:</span></td>
                <td class="text-center">SI {{ $markNullable($captation->hipoteca, true) }}</td>
                <td class="text-center">NO {{ $markNullable($captation->hipoteca, false) }}</td>
                <td><span class="label">BANCO:</span> {{ $formatText($captation->banco) }}</td>
                <td colspan="2"><span class="label">ESTADO DEL TRAMITE:</span> {{ $formatText($captation->estado_tramite) }}</td>
            </tr>
        </table>

        <div class="lined-block">
            <div class="heading">VENTAJAS Y BENEFICIOS:</div>
            @for ($index = 0; $index < 5; $index++)
                <div class="line">{{ $index === 0 ? $formatText($captation->ventajas_beneficios) : '' }}</div>
            @endfor
        </div>
    </div>

    <div class="page">
        <table class="header-table">
            <tr>
                <td class="brand">
                    <img src="{{ public_path('images/logo.png') }}" alt="Inmobipina">
                </td>
                <td class="right-brand">
                    Camara Inmobiliaria<br>de Venezuela<br>Nro. 84
                </td>
            </tr>
        </table>

        <div class="lined-block">
            <div class="heading">OBSERVACIONES Y/O RECOMENDACIONES:</div>
            @for ($index = 0; $index < 6; $index++)
                <div class="line">{{ $index === 0 ? $formatText($captation->observaciones_recomendaciones) : '' }}</div>
            @endfor
        </div>

        <div class="authorization-title">AUTORIZACION PARA PRESTACION DE SERVICIO</div>

        <div class="paragraph">
            Yo, <strong>{{ $autorizacionNombre }}</strong>, {{ $formatText($captation->autorizacion_nacionalidad, 'venezolano(a)') }}, mayor de edad,
            titular de la cedula de identidad <strong>{{ $formatText($captation->autorizacion_cedula) }}</strong>, en mi caracter de
            <strong>{{ $formatText($captation->autorizacion_caracter) }}</strong>, por medio de la presente AUTORIZO a la sociedad
            mercantil <strong>INVERSIONES PINANGO, C.A. (INMOBIPINA)</strong>, inscrita en el registro de Informacion Fiscal (RIF)
            No. J-29788405-0, para promocionar en Venta {{ $mark($captation->autoriza_venta) }} y/o Alquiler {{ $mark($captation->autoriza_alquiler) }},
            un inmueble constituido por <strong>{{ $autorizacionConstituido }}</strong> ubicado en <strong>{{ $autorizacionUbicacion }}</strong>,
            fijando el precio de venta y/o alquiler en <strong>{{ $autorizacionPrecio }}</strong>. A su vez me comprometo a cancelar a la empresa inmobiliaria antes descrita la cantidad de
            <strong>{{ $formatText($captation->autorizacion_comision) }}</strong> por concepto de comision, por Honorarios Inmobiliarios al momento de concretar la negociacion a traves de nuestra empresa.
            Asi mismo, concedo el permiso a la empresa antes mencionada, para promocionar el inmueble a traves de:
        </div>

        <table class="platform-grid">
            <tr>
                <th>MEDIO/PLATAFORMA</th>
                <th>SI</th>
                <th>NO</th>
            </tr>
            <tr>
                <td>INSTAGRAM/FACEBOOK</td>
                <td class="text-center">{{ $markNullable($captation->medio_instagram_facebook, true) }}</td>
                <td class="text-center">{{ $markNullable($captation->medio_instagram_facebook, false) }}</td>
            </tr>
            <tr>
                <td>PENDON/STICKER</td>
                <td class="text-center">{{ $markNullable($captation->medio_pendon_sticker, true) }}</td>
                <td class="text-center">{{ $markNullable($captation->medio_pendon_sticker, false) }}</td>
            </tr>
            <tr>
                <td>VIDEO PUBLICITARIO</td>
                <td class="text-center">{{ $markNullable($captation->medio_video_publicitario, true) }}</td>
                <td class="text-center">{{ $markNullable($captation->medio_video_publicitario, false) }}</td>
            </tr>
        </table>

        <div class="signature">FIRMA</div>
    </div>
</body>
</html>
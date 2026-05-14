<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Contrato de Exclusividad</title>
    <style>
        @page {
            margin: 26px 30px;
        }

        body { 
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.65;
            color: #222;
            margin: 0;
            padding: 0;
            text-align: justify; 
        }
        .container { width: 100%; margin: 0 auto; }
        .page { width: 100%; }
        .page + .page { page-break-before: always; }
        .row::after { content: ""; clear: both; display: table; }
        .col-6 { width: 50%; float: left; text-align: center; }

        .header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .header td {
            vertical-align: top;
        }

        .logo {
            width: 170px;
        }

        .logo img {
            width: 155px;
        }

        .chamber {
            text-align: right;
            font-size: 10px;
            font-weight: bold;
        }

        .line-placeholder {
            display: inline-block;
            border-bottom: 1px solid black;
            min-width: 200px;
            margin: 0 5px;
        }

        .signature-box {
            display: inline-block;
            width: 45%;
            text-align: center;
            margin-top: 60px;
        }

        .signature-box div { 
            border-top: 1px solid black; 
            padding-top: 5px; 
            width: 80%;
            margin: 0 auto;
        }

        p { margin-bottom: 1rem; }

        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        .mt-3 { margin-top: 1rem; }

        .page-break { page-break-after: always; }

    </style>
</head>
<body>
    @php
        $ownerName = $propietario['nombre'] ?: 'N/D';
        $ownerCi = $propietario['ci'] ?: 'N/D';
        $ownerRif = $propietario['rif'] ?: 'N/D';
        $ownerEmail = $propietario['email'] ?: 'N/D';
        $ownerPhone = $propietario['phone'] ?: 'N/D';
        $propertyDescription = $property['inmueble_descripcion'] ?: 'Inmueble no especificado';
        $propertyAddress = $property['address'] ?: 'N/D';
        $parish = isset($exclusivity) && $exclusivity->parroquia ? $exclusivity->parroquia : 'N/D';
        $area = $property['square_meters'] ?: 'N/D';
        $registroFecha = isset($exclusivity) && $exclusivity->registro_fecha
            ? \Carbon\Carbon::parse($exclusivity->registro_fecha)->locale('es')->isoFormat('DD [de] MMMM [de] YYYY')
            : 'N/D';
        $registroNumero = isset($exclusivity) && $exclusivity->registro_numero ? $exclusivity->registro_numero : 'N/D';
        $registroFolio = isset($exclusivity) && $exclusivity->registro_folio ? $exclusivity->registro_folio : 'N/D';
        $registroTomo = isset($exclusivity) && $exclusivity->registro_tomo ? $exclusivity->registro_tomo : 'N/D';
        $registroProtocolo = isset($exclusivity) && $exclusivity->registro_protocolo ? $exclusivity->registro_protocolo : 'N/D';
        $registroAnio = isset($exclusivity) && $exclusivity->registro_anio ? $exclusivity->registro_anio : 'N/D';
        $salePriceValue = isset($exclusivity) && $exclusivity->precio_venta ? $exclusivity->precio_venta : ($property['price'] ?? null);
        $salePrice = $salePriceValue ? number_format($salePriceValue, 2) . ' DOLARES AMERICANOS (USD ' . number_format($salePriceValue, 2) . ')' : 'N/D';
        $contractStartDate = isset($exclusivity) && $exclusivity->start_date
            ? \Carbon\Carbon::parse($exclusivity->start_date)->locale('es')->isoFormat('DD [de] MMMM [de] YYYY')
            : null;
        $contractEndDate = isset($exclusivity) && $exclusivity->end_date
            ? \Carbon\Carbon::parse($exclusivity->end_date)->locale('es')->isoFormat('DD [de] MMMM [de] YYYY')
            : null;
    @endphp
    <div class="container">
        <div class="page">
            <table class="header">
                <tr>
                    <td class="logo">
                        <img src="{{ public_path('images/logo.png') }}" alt="Inmobipina">
                    </td>
                    <td class="chamber">
                        Camara Inmobiliaria<br>de Venezuela<br>Nro. 84
                    </td>
                </tr>
            </table>

            <h3 class="text-center fw-bold">CONTRATO DE EXCLUSIVIDAD DE BIEN INMUEBLE</h3>

            <p>Entre el ciudadano, <strong style="text-transform: uppercase;">{{ $ownerName }}</strong>, venezolano, mayor de edad, civilmente hábil, portador de la cédula de identidad No.: {{ $ownerCi }} e inscrito en el Registro de Información Fiscal (R.I.F) No.: {{ $ownerRif }}, quien a los efectos de este Contrato se denominará <strong>"EL PROPIETARIO"</strong> por una parte y por la otra, la sociedad mercantil <strong>INVERSIONES PIÑANGO C.A.</strong>, debidamente inscrita en Registro Mercantil Segundo de la Circunscripción Judicial del Estado Bolívar, 22/06/2009, quedando anotado bajo el Nº 64, Tomo 32 A-PRO, representada en este acto por el ciudadano <strong>LUIS RAFAEL PIÑANGO CARRY</strong>, venezolano, mayor de edad, titular de la Cédula de Identidad número V-5.907.985 y de este domicilio, quien para los efectos de este Contrato se denominará <strong>"LA INMOBILIARIA"</strong>, se ha convenido en celebrar el presente <strong>CONTRATO DE EXCLUSIVIDAD DE VENTA DEL BIEN INMUEBLE</strong>, el cual contendrá las siguientes disposiciones:</p>

            <p><span class="fw-bold">PRIMERA:</span> <strong>"EL PROPIETARIO"</strong> otorga Autorización de Venta de un (01) Inmueble, constituido por <strong>{{ $propertyDescription }}</strong>, ubicado en <strong>{{ $propertyAddress }}</strong>, Parroquia <strong>{{ $parish }}</strong> de Puerto Ordaz, Municipio Autónomo Caroní del Estado Bolívar; el referido lote de terreno tiene un área aproximada de <strong>{{ $area }} METROS CUADRADOS ({{ $area }} mts²)</strong>; y le pertenece a <strong>EL PROPIETARIO</strong> según documento debidamente Protocolizado ante el Registro Público del Municipio Caroní del Estado Bolívar, en fecha <strong>{{ $registroFecha }}</strong>, Registrado bajo el número <strong>{{ $registroNumero }}</strong>, Folio <strong>{{ $registroFolio }}</strong>, Tomo <strong>{{ $registroTomo }}</strong>, Protocolo <strong>{{ $registroProtocolo }}</strong> del año <strong>{{ $registroAnio }}</strong>. Queda entendido que <strong>"LA INMOBILIARIA"</strong> utilizará sus propios elementos y realizará las gestiones necesarias para la materialización de dicha venta.</p>

            <p><span class="fw-bold">SEGUNDA:</span> <strong>"EL PROPIETARIO"</strong> se compromete a pagarle a <strong>"LA INMOBILIARIA"</strong> por concepto de Comisión de Ventas el cinco por ciento (5%) sobre el valor del inmueble que se establezca para la Venta Definitiva del mismo. Quedando establecido que dicha comisión, será cancelada a <strong>"LA INMOBILIARIA"</strong> una vez que se materialice dicho pago, ya sea en la firma de la Opción a Compra por ante la Notaria o la Venta Definitiva por ante el Registro Público del Estado Bolívar. <strong>"EL PROPIETARIO"</strong> fija el precio del inmueble en <strong>{{ $salePrice }}</strong> pudiendo ser negociable.</p>

            <p><span class="fw-bold">TERCERA:</span> <strong>"LA INMOBILIARIA"</strong> queda facultada para ofertar, publicitar y en general presentar al mercado que considere pertinente, quedando entendido y aceptado que correrán por cuenta de <strong>"LA INMOBILIARIA"</strong>, los gastos que se originen en virtud de la Promoción y Publicidad del inmueble ya descrito.</p>

            <p><span class="fw-bold">CUARTA:</span> <strong>"LA INMOBILIARIA"</strong> se compromete a efectuar las gestiones de Promoción y Publicidad del Inmueble objeto de venta @if($contractStartDate && $contractEndDate) en el lapso comprendido entre el <strong>{{ $contractStartDate }}</strong> y el <strong>{{ $contractEndDate }}</strong> @else en un lapso de NOVENTA (90) días continuos contados a partir de la firma de este documento @endif, pudiendo ser renovable por el mismo periodo de tiempo, durante dicho periodo <strong>"LA INMOBILIARIA"</strong> será la única intermediaria sobre cualquier negocio sobre esta propiedad. Queda establecido, que, si en el lapso de vigencia de este contrato <strong>"EL PROPIETARIO"</strong> decide vender el inmueble por sus propios medios, deberá cancelar a <strong>"LA INMOBILIARIA"</strong> la cantidad de dos puntos cinco por ciento (2,5%) del monto total del precio de venta.</p>

            <p><span class="fw-bold">QUINTA:</span> <strong>"LA INMOBILIARIA"</strong> no asume responsabilidad alguna por Vicios Ocultos o daños visibles que puedan poseer el inmueble objeto de esta Venta.</p>

            <p><span class="fw-bold">SEXTA:</span> En caso de que el inmueble objeto de esta venta sufra algún daño por caso fortuito o fuerza mayor, que impida la continuidad de ejecutar dicha Venta, queda automáticamente resuelto el presente contrato.</p>
        </div>

        <div class="page">
            <table class="header">
                <tr>
                    <td class="logo">
                        <img src="{{ public_path('images/logo.png') }}" alt="Inmobipina">
                    </td>
                    <td class="chamber">
                        Camara Inmobiliaria<br>de Venezuela<br>Nro. 84
                    </td>
                </tr>
            </table>

            <p><span class="fw-bold">SÉPTIMA:</span> Se establece como domicilio a los fines de resolver cualquier conflicto Judicial o Extrajudicial Ciudad Guayana, Estado Bolívar. Las Notificaciones que llegasen a efectuarse deben ser dirigidas en el caso de <strong>"EL PROPIETARIO"</strong>, Correo Electrónico: <strong>{{ $ownerEmail }}</strong>, número telefónico: <strong>{{ $ownerPhone }}</strong> y el de <strong>"LA INMOBILIARIA"</strong> carrera Nekuima, Edificio Multicentro, Alta Vista, Correo Electrónico: <strong>inmobipina@hotmail.com</strong>, número telefónico: <strong>N/D</strong>. Las notificaciones electrónicas se regirán por lo dispuesto en la Ley de Firmas y Datos Electrónicos.</p>

            <p>Acuerdo que se suscribe en, Puerto Ordaz a los <strong>{{ $fecha_contrato }}</strong>, a petición de las partes interesadas.</p>

            <div class="row">
                <div class="col-6 signature-box">
                    <div>{{ $ownerName }}<br>"EL PROPIETARIO"</div>
                </div>
                <div class="col-6 signature-box">
                    <div>INVERSIONES PIÑANGO C.A.<br>"LA INMOBILIARIA"</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

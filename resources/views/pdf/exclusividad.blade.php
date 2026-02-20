<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Contrato de Exclusividad</title>
    <style>
        body { 
            font-family: 'Arial Narrow', sans-serif; 
            font-size: 12pt; 
            line-height: 1.8; 
            padding: 30px;
            text-align: justify; 
        }
        .container { width: 100%; margin: auto; page-break-after: always; }
        .row::after { content: ""; clear: both; display: table; }
        .col-6 { width: 50%; float: left; text-align: center; }
        
        .header-img {
            width: 150px;
            opacity: 0.2;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
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
    <div class="container">
        <!-- Encabezado -->
        <div class="header">
            <img src="{{ public_path('images/logo.png') }}" class="header-img" alt="Logo izquierda">
            <img src="{{ public_path('images/logo.png') }}" class="header-img" alt="Logo derecha">
        </div>

        <h3 class="text-center fw-bold">CONTRATO DE EXCLUSIVIDAD DE BIEN INMUEBLE</h3>

        <p>Entre el ciudadano, <strong style="text-transform: uppercase;">{{ $propietario['nombre'] }}</strong>, venezolano, mayor de edad, civilmente hábil, portador de la cédula de identidad No.: V-{{ $propietario['ci'] }} e inscrito en el Registro de Información Fiscal (R.I.F) No.: V-{{ $propietario['rif'] }}, quien a los efectos de este Contrato se denominará <strong>"EL PROPIETARIO"</strong> por una parte y por la otra, la sociedad mercantil <strong>INVERSIONES PIÑANGO C.A.</strong>, debidamente inscrita en Registro Mercantil Segundo de la Circunscripción Judicial del Estado Bolívar, 22/06/2009, quedando anotado bajo el Nº 64, Tomo 32 A-PRO, representada en este acto por el ciudadano <strong>LUIS RAFAEL PIÑANGO CARRY</strong>, venezolano, mayor de edad, titular de la Cédula de Identidad número V-5.907.985 y de este domicilio, quien para los efectos de este Contrato se denominará <strong>"LA INMOBILIARIA"</strong>, se ha convenido en celebrar el presente <strong>CONTRATO DE EXCLUSIVIDAD DE VENTA DEL BIEN INMUEBLE</strong>, el cual contendrá las siguientes disposiciones:</p>

        <p><span class="fw-bold">PRIMERA:</span> <strong>"EL PROPIETARIO"</strong> otorga Autorización de Venta de un (01) Inmueble, constituido por <strong>{{ $property['inmueble_descripcion'] ?: '___________________________________' }}</strong>, ubicado en <strong>{{ $property['address'] }}</strong>, Parroquia <strong>{{ isset($exclusivity) ? $exclusivity->parroquia : 'Cachamay' }}</strong> de Puerto Ordaz, Municipio Autónomo Caroní del Estado Bolívar; el referido lote de terreno tiene un área aproximada de <strong>{{ $property['square_meters'] }} METROS CUADRADOS ({{ $property['square_meters'] }} mts²)</strong>; y le pertenece a <strong>EL PROPIETARIO</strong> según documento debidamente Protocolizado ante el Registro Público del Municipio Caroní del Estado Bolívar, en fecha <strong>{{ isset($exclusivity) && $exclusivity->registro_fecha ? \Carbon\Carbon::parse($exclusivity->registro_fecha)->locale('es')->isoFormat('DD [de] MMMM [de] YYYY') : '____ de ________ de ____' }}</strong>, Registrado bajo el número <strong>{{ isset($exclusivity) ? ($exclusivity->registro_numero ?: '__') : '__' }}</strong>, Folio <strong>{{ isset($exclusivity) ? ($exclusivity->registro_folio ?: '__') : '__' }}</strong>, Tomo <strong>{{ isset($exclusivity) ? ($exclusivity->registro_tomo ?: '__') : '__' }}</strong> del Protocolo de Transcripción del año <strong>{{ isset($exclusivity) ? ($exclusivity->registro_anio ?: '____') : '____' }}</strong>. Queda entendido que <strong>"LA INMOBILIARIA"</strong> utilizará sus propios elementos y realizará las gestiones necesarias para la materialización de dicha venta.</p>

        <p><span class="fw-bold">SEGUNDA:</span> <strong>"EL PROPIETARIO"</strong> se compromete a pagarle a <strong>"LA INMOBILIARIA"</strong> por concepto de Comisión de Ventas el cinco por ciento (5%) sobre el valor del inmueble que se establezca para la Venta Definitiva del mismo. Quedando establecido que dicha comisión, será cancelada a <strong>"LA INMOBILIARIA"</strong> una vez que se materialice dicho pago, ya sea en la firma de la Opción a Compra por ante la Notaria o la Venta Definitiva por ante el Registro Público del Estado Bolívar. <strong>"EL PROPIETARIO"</strong> fija el precio del inmueble en <strong>{{ isset($exclusivity) && $exclusivity->precio_venta ? number_format($exclusivity->precio_venta, 2) . ' DÓLARES AMERICANOS (USD ' . number_format($exclusivity->precio_venta, 2) . ')' : '_________________ DÓLARES AMERICANOS (USD 00.000$)' }}</strong> pudiendo ser negociable.</p>

        <p><span class="fw-bold">TERCERA:</span> <strong>"LA INMOBILIARIA"</strong> queda facultada para ofertar, publicitar y en general presentar al mercado que considere pertinente, quedando entendido y aceptado que correrán por cuenta de <strong>"LA INMOBILIARIA"</strong>, los gastos que se originen en virtud de la Promoción y Publicidad del inmueble ya descrito.</p>

        <p><span class="fw-bold">CUARTA:</span> <strong>"LA INMOBILIARIA"</strong> se compromete a efectuar las gestiones de Promoción y Publicidad del Inmueble objeto de venta en un lapso de NOVENTA (90) días continuos contados a partir de la firma de este documento, pudiendo ser renovable por el mismo periodo de tiempo, durante dicho periodo <strong>"LA INMOBILIARIA"</strong> será la única intermediaria sobre cualquier negocio sobre esta propiedad. Queda establecido, que, si en el lapso de vigencia de este contrato <strong>"EL PROPIETARIO"</strong> decide vender el inmueble por sus propios medios, deberá cancelar a <strong>"LA INMOBILIARIA"</strong> la cantidad de dos puntos cinco por ciento (2,5%) del monto total del precio de venta.</p>

        <p><span class="fw-bold">QUINTA:</span> <strong>"LA INMOBILIARIA"</strong> no asume responsabilidad alguna por Vicios Ocultos o daños visibles que puedan poseer el inmueble objeto de esta Venta.</p>

        <p><span class="fw-bold">SEXTA:</span> En caso de que el inmueble objeto de esta venta sufra algún daño por caso fortuito o fuerza mayor, que impida la continuidad de ejecutar dicha Venta, queda automáticamente resuelto el presente contrato.</p>

        <!-- Encabezado de página 2 -->
        <div class="header">
            <img src="{{ public_path('images/logo.png') }}" class="header-img" alt="Logo izquierda">
            <img src="{{ public_path('images/logo.png') }}" class="header-img" alt="Logo derecha">
        </div>

        <p><span class="fw-bold">SÉPTIMA:</span> Se establece como domicilio a los fines de resolver cualquier conflicto Judicial o Extrajudicial Ciudad Guayana, Estado Bolívar. Las Notificaciones que llegasen a efectuarse deben ser dirigidas en el caso de <strong>"EL PROPIETARIO"</strong>, Correo Electrónico: <strong>{{ $propietario['email'] ?: '___________________________' }}</strong>, número telefónico: <strong>{{ $propietario['phone'] ?: '__________________' }}</strong> y el de <strong>"LA INMOBILIARIA"</strong> carrera Nekuima, Edificio Multicentro, Alta Vista, Correo Electrónico: <strong>inmobipina@hotmail.com</strong>, número telefónico: ___________________. Las notificaciones electrónicas se regirán por lo dispuesto en la Ley de Firmas y Datos Electrónicos.</p>

        <p>Acuerdo que se suscribe en, Puerto Ordaz a los <strong>{{ $fecha_contrato }}</strong>, a petición de las partes interesadas.</p>

        <div class="row">
            <div class="col-6 signature-box">
                <div>"EL PROPIETARIO"</div>
            </div>
            <div class="col-6 signature-box">
                <div>"LA INMOBILIARIA"</div>
            </div>
        </div>
    </div>
</body>
</html>

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
            justify-content: space-between; /* Empuja las imágenes a los extremos */
            align-items: center; /* Centra verticalmente si tienen alturas distintas */
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

        <p>Entre el ciudadano, <strong style=" text-transform: uppercase;">{{$propietario['nombre']}}</strong>, venezolano, mayor de edad, civilmente hábil, portador de la cédula de identidad No.: V-{{$propietario['ci']}} e inscrito en el Registro de Información Fiscal (R.I.F) No.: V-{{$propietario['rif']}}, quien a los efectos de este Contrato se denominará <strong>“EL PROPIETARIO”</strong> por una parte y por la otra, la sociedad mercantil <strong>INVERSIONES PIÑANGO C.A.</strong>, representada en este acto por el ciudadano  <strong>LUIS RAFAEL PIÑANGO CARRY</strong>, quien para los efectos de este Contrato se denominará <strong>“LA INMOBILIARIA”</strong>, se ha convenido en celebrar el presente <strong>CONTRATO DE EXCLUSIVIDAD DE VENTA DEL BIEN INMUEBLE</strong>, el cual contendrá las siguientes disposiciones:
        <span class="fw-bold">PRIMERA:</span>  <strong>“EL PROPIETARIO”</strong> otorga autorización de venta de un inmueble, constituido por >XXX</span>, ubicado en {{$property['address']}}</span>, Parroquia Cachamay de Puerto Ordaz, Municipio Autónomo Caroní del Estado Bolívar; el lote de terreno tiene un área aproximada de {{$property['square_meters']}} metros cuadrados (mts²); y le pertenece a <strong>EL PROPIETARIO</strong> según documento protocolizado ante el Registro Público del Municipio Caroní del Estado Bolívar, en fecha XX de mayo de 20XX, registrado bajo el número X, folio XX, tomo XX del protocolo de transcripción del año 20XX. Queda entendido que  <strong>“LA INMOBILIARIA”</strong> utilizará sus propios elementos para la venta. 
        <span class="fw-bold">SEGUNDA:</span>  <strong>“EL PROPIETARIO”</strong> se compromete a pagar a <strong>“LA INMOBILIARIA”</strong> una comisión del 5% sobre el valor del inmueble: {{$property['price'] *0.05}} <strong>DOLARES AMERICANOS (USD 00.000$</strong>).
        <span class="fw-bold">TERCERA:</span>  <strong>“LA INMOBILIARIA”</strong> queda facultada para publicitar el inmueble, corriendo por su cuenta los gastos de promoción.
        <span class="fw-bold">CUARTA:</span> El contrato tendrá una vigencia de >90 días continuos, renovables si ambas partes lo acuerdan. Si <strong>“EL PROPIETARIO”</strong> vende el inmueble por sus propios medios, deberá pagar a <strong>“LA INMOBILIARIA”</strong> 2,5% del monto total.
        <span class="fw-bold">QUINTA:</span>  <strong>“LA INMOBILIARIA”</strong> no asume responsabilidad por vicios ocultos o daños visibles del inmueble.
        <span class="fw-bold">SEXTA:</span> En caso de daños por fuerza mayor que impidan la venta, queda automáticamente resuelto el presente contrato.</p>

        <!-- Encabezado de página 2 -->
        <div class="header">
            <img src="{{ public_path('images/logo.png') }}" class="header-img" alt="Logo izquierda">
            <img src="{{ public_path('images/logo.png') }}" class="header-img" alt="Logo derecha">
        </div>

        <p><span class="fw-bold">SÉPTIMA:</span> Se establece como domicilio a los fines de resolver cualquier conflicto judicial o extrajudicial Ciudad Guayana, Estado Bolívar. Las notificaciones que lleguen a efectuarse deben ser dirigidas en el caso de  <strong>“EL PROPIETARIO”</strong>, Correo Electrónico: {{$propietario['email']}}, número telefónico: {{$propietario['phone']}} y en el caso de  <strong>“LA INMOBILIARIA”</strong>, correo electrónico: <strong>inmobipina@hotmail.com</strong>, número telefónico: ___________________. Las notificaciones electrónicas se regirán por lo dispuesto en la Ley de Firmas y Datos Electrónicos.</p>

        <p>Acuerdo que se suscribe en Puerto Ordaz a los  <strong>ocho (08) días del mes de octubre del año 2025</strong>, a petición de las partes interesadas.</p>

        <div class="row">
            <div class="col-6 signature-box">
                <div>“EL PROPIETARIO”</div>
            </div>
            <div class="col-6 signature-box">
                <div>“LA INMOBILIARIA”</div>
            </div>
        </div>
    </div>
</body>
</html>

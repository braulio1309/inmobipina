<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 40px 50px;
        }
        body {
            font-family: 'Helvetica', sans-serif;
            color: #1e1e1e;
            font-size: 13px;
            line-height: 1.5;
        }
        h1, h2, h3, h4 {
            text-transform: uppercase;
            font-weight: bold;
            margin: 0;
        }
        h1 {
            font-size: 28px;
            text-align: center;
            color: #0070c0;
            margin-bottom: 10px;
        }
        h2 {
            font-size: 18px;
            color: #0070c0;
            margin-top: 30px;
            border-bottom: 2px solid #0070c0;
            padding-bottom: 4px;
        }
        h3 {
            font-size: 15px;
            color: #333;
            margin-top: 20px;
        }
        p {
            margin-bottom: 10px;
            text-align: justify;
        }
        .highlight {
            color: #e74c3c;
            font-weight: bold;
        }
        .section {
            margin-top: 20px;
        }
        .intro {
            background: #f2f6fc;
            border-left: 5px solid #0070c0;
            padding: 15px 20px;
            border-radius: 5px;
        }
        .quote {
            font-style: italic;
            color: #0070c0;
            text-align: center;
            margin: 25px 0;
            font-size: 14px;
        }
        .data-table, .diet-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .data-table th, .data-table td,
        .diet-table th, .diet-table td {
            border: 1px solid #ccc;
            padding: 8px 10px;
        }
        .data-table th {
            background-color: #e8f1fb;
            text-align: left;
            font-weight: 600;
        }
        .diet-table th {
            background-color: #f5f5f5;
            text-transform: uppercase;
            font-weight: 600;
            text-align: left;
        }
        .section-title {
            color: #0070c0;
            font-weight: bold;
            font-size: 16px;
            text-transform: uppercase;
            margin-top: 35px;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>

    {{-- Portada --}}
    <div style="text-align: center; margin-top: 150px;">
        <h1>Programa Nutricional Personalizado</h1>
        <h3 style="color: #666;">{{ strtoupper($user->name) }}</h3>
        <p style="margin-top: 20px;">Plan adaptado a tu objetivo: <strong>{{ ucfirst($user->goal) }}</strong></p>
        <div class="quote">“La constancia vence a la fuerza.”</div>
    </div>

    <div class="page-break"></div>

    {{-- Introducción --}}
    <div class="intro">
        <p>
            Este programa ha sido diseñado especialmente para <strong>{{ $user->name }}</strong> considerando su estado actual,
            sus preferencias alimenticias y el objetivo de <strong>{{ strtolower($user->goal) }}</strong>.
            La clave está en mantener la <strong>disciplina, constancia y compromiso</strong> con cada paso del proceso.
        </p>
    </div>

    <div class="quote">“Tu cuerpo puede lograr todo lo que tu mente crea posible.”</div>

    {{-- Datos del usuario --}}
    <h2>Datos personales</h2>
    <table class="data-table">
        <tr><th>Nombre</th><td>{{ $user->name }}</td></tr>
        <tr><th>Edad</th><td>{{ \Carbon\Carbon::parse($user->birth_date)->age }} años</td></tr>
        <tr><th>Peso</th><td>{{ $user->weight_kg }} kg</td></tr>
        <tr><th>Altura</th><td>{{ $user->height_cm }} cm</td></tr>
        <tr><th>Género</th><td>{{ $user->gender }}</td></tr>
        <tr><th>Nivel de actividad</th><td>{{ $user->activity_level }}</td></tr>
        <tr><th>Objetivo</th><td>{{ $user->goal }}</td></tr>
        <tr><th>Alergias</th><td>{{ $user->allergies ?: 'Ninguna' }}</td></tr>
        <tr><th>Preferencias</th><td>{{ $user->preferences ?: 'Sin especificar' }}</td></tr>
    </table>

    <h2>Cálculos nutricionales</h2>
    <table class="data-table">
        <tr><th>TMB</th><td>{{ $data['tmb'] ?? '---' }} kcal</td></tr>
        <tr><th>Gasto total</th><td>{{ $data['total_expenditure'] ?? '---' }} kcal</td></tr>
        <tr><th>Calorías objetivo</th><td>{{ $data['target_calories'] ?? '---' }} kcal</td></tr>
        <tr><th>Proteínas</th><td>{{ $data['protein'] ?? '---' }} g</td></tr>
        <tr><th>Carbohidratos</th><td>{{ $data['carbs'] ?? '---' }} g</td></tr>
        <tr><th>Grasas</th><td>{{ $data['fats'] ?? '---' }} g</td></tr>
    </table>

    <div class="page-break"></div>

    {{-- Plan de comidas --}}
    <h2>Plan alimenticio diario</h2>

    @foreach ($data['meals'] ?? [] as $mealName => $options)
        <h3>{{ ucfirst($mealName) }}</h3>
        <table class="diet-table">
            <thead>
                <tr>
                    <th>Opción</th>
                    <th>Descripción</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($options as $index => $option)
                    <tr>
                        <td>Opción {{ $index + 1 }}</td>
                        <td>{{ $option }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

    {{-- Observaciones --}}
    <h2>Observaciones del entrenador</h2>
    <p>{{ $user->observations ?: 'Sin observaciones adicionales.' }}</p>

    {{-- Cierre motivacional --}}
    <div class="quote">
        {{ $data['motivation'] ?? 'Recuerda: no existen resultados sin constancia. Cada comida es una oportunidad de mejorar.' }}
    </div>

</body>
</html>

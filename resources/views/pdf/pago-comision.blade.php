<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Pago de Comision</title>
    <style>
        @page {
            margin: 26px 30px;
        }

        body {
            font-family: Arial, sans-serif;
            color: #222;
            margin: 0;
            font-size: 14px;
            line-height: 1.7;
        }

        .page + .page {
            page-break-before: always;
        }

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
            font-size: 12px;
            font-weight: bold;
        }

        .date {
            text-align: right;
            font-size: 14px;
            margin: 8px 0 30px;
        }

        h1 {
            text-align: center;
            font-size: 20px;
            margin: 0 0 18px;
            font-weight: bold;
        }

        p {
            margin: 0 0 14px;
            text-align: justify;
        }

        .commission-table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0 16px;
            font-size: 13px;
        }

        .commission-table th {
            background: #f0f0f0;
            border: 1px solid #ccc;
            padding: 5px 8px;
            text-align: left;
        }

        .commission-table td {
            border: 1px solid #ddd;
            padding: 5px 8px;
        }

        .commission-table .highlight {
            background: #fafcff;
            font-weight: bold;
        }

        .closure-info {
            background: #f8f8f8;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px 14px;
            margin-bottom: 16px;
            font-size: 13px;
        }

        .closure-info dt {
            font-weight: bold;
            display: inline;
        }

        .closure-info dd {
            display: inline;
            margin: 0 0 4px 4px;
        }

        .closure-info .info-row {
            margin-bottom: 4px;
        }

        .line {
            display: inline-block;
            border-bottom: 1px solid #444;
            min-width: 120px;
            padding: 0 4px 2px;
        }

        .line.wide {
            min-width: 220px;
        }

        .line.short {
            min-width: 90px;
        }

        .signature-wrap {
            margin-top: 90px;
            width: 240px;
            text-align: center;
        }

        .signature-line {
            border-top: 1px solid #444;
            margin-bottom: 8px;
        }

        .signature-title {
            font-weight: bold;
            margin-bottom: 12px;
        }
    </style>
</head>
<body>
    @php
        $formatAmount = function ($value) {
            return number_format((float) $value, 2, ',', '.');
        };

        $formatDateText = function ($date) {
            return $date->locale('es')->isoFormat('DD [de] MMMM [del] YYYY');
        };

        $formatFullName = function ($person) {
            $name = trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? ''));

            return $name !== '' ? $name : trim((string) ($person->name ?? $person->email ?? ''));
        };

        $spellAmount = function ($value) {
            $value = (float) $value;

            if (class_exists('NumberFormatter')) {
                $formatter = new \NumberFormatter('es', \NumberFormatter::SPELLOUT);
                $integerPart = (int) floor($value);
                $decimalPart = (int) round(($value - $integerPart) * 100);
                $text = $formatter->format($integerPart);

                if ($decimalPart > 0) {
                    $text .= ' con ' . $formatter->format($decimalPart) . ' centavos';
                }

                return mb_strtoupper($text . ' dolares', 'UTF-8');
            }

            return mb_strtoupper(number_format($value, 2, ',', '.') . ' DOLARES', 'UTF-8');
        };

        $propertyType = trim((string) ($property->type ?? '')) ?: 'Inmueble';
        $propertyAddress = trim((string) ($property->address ?? ''));
        $operationLabel = strtoupper($operation->type ?? 'VENTA');
        $propertyPrice = $operation->property_price ?: ($property->price ?? 0);
        $totalCommissionPct = (float) ($operation->total_commission_percentage ?? 5);
        $totalCommissionAmt = (float) ($operation->total_commission_amount ?? 0);
        $companyPctOfOperation = (float) ($operation->company_commission_percentage ?? 0);
        $companyAmt = (float) ($operation->company_commission_amount ?? 0);
        $ownerName = trim((string) ($ownerClient?->name ?? ''));
        $ownerCi = trim((string) ($ownerClient?->ci ?? ''));
        $buyerName = trim((string) ($buyerClient?->name ?? ''));
        $buyerCi = trim((string) ($buyerClient?->ci ?? ''));
        $operationDate = $operation->fecha_cierre
            ? \Carbon\Carbon::parse($operation->fecha_cierre)->locale('es')->isoFormat('DD/MM/YYYY')
            : ($operation->end_date
                ? \Carbon\Carbon::parse($operation->end_date)->locale('es')->isoFormat('DD/MM/YYYY')
                : null);
        $today = now();

        $receipts = collect([
            [
                'name' => $companyRepresentative['name'],
                'ci' => $companyRepresentative['ci'],
                'email' => null,
                'amount' => (float) ($operation->company_commission_amount ?? 0),
                'share_percentage' => $totalCommissionPct > 0
                    ? round(($companyPctOfOperation / $totalCommissionPct) * 100, 2)
                    : 0,
                'is_company' => true,
            ],
        ])->merge($advisors->map(function ($advisor) use ($formatFullName, $totalCommissionPct) {
            $advisorName = $formatFullName($advisor);
            $advisorCommission = (float) ($advisor->pivot->commission_amount ?? 0);
            $advisorPctOfOperation = (float) ($advisor->pivot->commission_percentage ?? 0);

            return [
                'name' => $advisorName,
                'ci' => trim((string) ($advisor->ci ?? '')),
                'email' => $advisor->email ?? null,
                'amount' => $advisorCommission,
                'share_percentage' => ($advisorPctOfOperation > 0 && $totalCommissionPct > 0)
                    ? round(($advisorPctOfOperation / $totalCommissionPct) * 100, 2)
                    : 0,
                'is_company' => false,
            ];
        }));
    @endphp

    @foreach($receipts as $receipt)
        @php
            $recipientName = $receipt['name'];
            $recipientEmail = $receipt['email'] ?? '';
            $recipientAmount = (float) $receipt['amount'];
            $commissionSharePct = (float) $receipt['share_percentage'];
            $totalCommission = $commissionSharePct > 0 ? ($recipientAmount * 100 / $commissionSharePct) : ($recipientAmount * 2);
            $recipientCi = $receipt['ci'];
        @endphp
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

            <div class="date">Puerto Ordaz, {{ $today->format('d') }} de {{ $today->locale('es')->isoFormat('MMMM') }} del {{ $today->format('Y') }}</div>

            <h1>PAGO DE COMISION</h1>

            <!-- Resumen del cierre -->
            <div class="closure-info">
                <div class="info-row"><dt>Operacion #:</dt> <dd>{{ $operation->id }} &mdash; <strong>{{ $operationLabel }}</strong></dd></div>
                <div class="info-row"><dt>Inmueble:</dt> <dd>{{ $propertyType }}{{ $propertyAddress ? ' &mdash; ' . $propertyAddress : '' }}</dd></div>
                <div class="info-row"><dt>Precio del cierre:</dt> <dd><strong>${{ $formatAmount($propertyPrice) }}</strong></dd></div>
                <div class="info-row"><dt>Monto de la operacion:</dt> <dd><strong>${{ $formatAmount($operation->amount ?? 0) }}</strong></dd></div>
                @if($operationDate)
                <div class="info-row"><dt>Fecha de cierre:</dt> <dd>{{ $operationDate }}</dd></div>
                @endif
                <div class="info-row"><dt>Propietario:</dt> <dd>{{ $ownerName ?: '—' }}{{ $ownerCi ? ' (C.I. ' . $ownerCi . ')' : '' }}</dd></div>
                <div class="info-row"><dt>Comprador/Arrendatario:</dt> <dd>{{ $buyerName ?: '—' }}{{ $buyerCi ? ' (C.I. ' . $buyerCi . ')' : '' }}</dd></div>
            </div>

            <!-- Tabla de comisiones del cierre -->
            <table class="commission-table">
                <thead>
                    <tr>
                        <th>Beneficiario</th>
                        <th>% Operacion</th>
                        <th>Monto Comision</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="{{ $receipt['is_company'] ? 'highlight' : '' }}">
                        <td><strong>{{ $companyRepresentative['name'] }}</strong> (Inmobiliaria)</td>
                        <td>{{ rtrim(rtrim(number_format($companyPctOfOperation, 4, '.', ''), '0'), '.') }}%</td>
                        <td><strong>${{ $formatAmount($companyAmt) }}</strong></td>
                    </tr>
                    @foreach($advisors as $adv)
                    @php
                        $advName = $formatFullName($adv);
                        $advAmt = (float) ($adv->pivot->commission_amount ?? 0);
                        $advPct = (float) ($adv->pivot->commission_percentage ?? 0);
                    @endphp
                    <tr class="{{ (!$receipt['is_company'] && $advName === $recipientName) ? 'highlight' : '' }}">
                        <td>{{ $advName }}{{ $adv->email ? ' (' . $adv->email . ')' : '' }}</td>
                        <td>{{ rtrim(rtrim(number_format($advPct, 4, '.', ''), '0'), '.') }}%</td>
                        <td>${{ $formatAmount($advAmt) }}</td>
                    </tr>
                    @endforeach
                    <tr style="background:#e8f4e8;">
                        <td><strong>TOTAL COMISION</strong></td>
                        <td><strong>{{ rtrim(rtrim(number_format($totalCommissionPct, 4, '.', ''), '0'), '.') }}%</strong></td>
                        <td><strong>${{ $formatAmount($totalCommissionAmt > 0 ? $totalCommissionAmt : ($companyAmt + $advisors->sum(fn($a) => (float)($a->pivot->commission_amount ?? 0)))) }}</strong></td>
                    </tr>
                </tbody>
            </table>

            <p>
                Yo, <span class="line wide">{{ $recipientName }}</span>, he recibido de la sociedad mercantil
                <strong>INVERSIONES PINANGO, C.A.</strong>, inscrita en el Registro de Informacion Fiscal (R.I.F)
                Nro. <strong>{{ $companyRepresentative['rif'] }}</strong>, la cantidad de
                <span class="line wide">{{ $spellAmount($recipientAmount) }}</span>
                (<span class="line short">{{ $formatAmount($recipientAmount) }} $</span>),
                por concepto de pago de comision del {{ rtrim(rtrim(number_format($commissionSharePct, 2, '.', ''), '0'), '.') }}% de
                <span class="line short">{{ $formatAmount($totalCommission) }} $</span>.
            </p>

            <p>
                Por concepto de <strong>{{ $operationLabel }}</strong>, de un inmueble constituido por un(a)
                <span class="line">{{ $propertyType }}</span>, ubicado en
                <span class="line wide">{{ $propertyAddress }}</span> de Ciudad Guayana, Municipio Caroni del Estado Bolivar,
                Propiedad del Senor(ra), <span class="line wide">{{ $ownerName }}</span>, Venezolano, Mayor de edad y de este domicilio,
                Titular de la cedula de identidad Nro. <span class="line">{{ $ownerCi }}</span> y el comprador/arrendatario
                de dicho inmueble <span class="line wide">{{ $buyerName }}</span>, mayor de edad, titular de la cedula
                de identidad Nro. <span class="line">{{ $buyerCi }}</span>, <strong>PRECIO DE:</strong>
                <span class="line">{{ $formatAmount($propertyPrice) }} $</span>.
            </p>

            <p>
                Recibo emitido para {{ $receipt['is_company'] ? 'la inmobiliaria' : 'el asesor' }}
                <strong>{{ $recipientName }}</strong>{{ $recipientEmail ? ' (' . $recipientEmail . ')' : '' }},
                relacionado con la operacion #{{ $operation->id }} y autorizado por <strong>{{ $companyRepresentative['name'] }}</strong>,
                C.I. {{ $companyRepresentative['ci'] }}.
            </p>

            <div class="signature-wrap">
                <div class="signature-line"></div>
                <div class="signature-title">RECIBE CONFORME</div>
                <div>{{ $recipientCi !== '' ? 'C.I. ' . $recipientCi : '' }}</div>
            </div>
        </div>
    @endforeach
</body>
</html>
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
            font-size: 12px;
            line-height: 1.65;
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
            font-size: 10px;
            font-weight: bold;
        }

        .date {
            text-align: right;
            font-size: 12px;
            margin: 8px 0 30px;
        }

        h1 {
            text-align: center;
            font-size: 18px;
            margin: 0 0 18px;
            font-weight: bold;
        }

        p {
            margin: 0 0 12px;
            text-align: justify;
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

            return $name !== '' ? $name : ($person->name ?? $person->email ?? 'N/D');
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

        $propertyType = $property->type ?? 'Inmueble';
        $propertyAddress = $property->address ?? 'N/D';
        $operationLabel = strtoupper($operation->type ?? 'VENTA');
        $propertyPrice = $operation->property_price ?: ($property->price ?? 0);
        $ownerName = $ownerClient?->name ?: 'N/D';
        $ownerCi = $ownerClient?->ci ?: 'N/D';
        $buyerName = $buyerClient?->name ?: 'N/D';
        $buyerCi = $buyerClient?->ci ?: 'N/D';
        $today = now();

        $receipts = collect([
            [
                'name' => $companyRepresentative['name'],
                'ci' => $companyRepresentative['ci'],
                'email' => null,
                'amount' => (float) ($operation->company_commission_amount ?? 0),
                'share_percentage' => 50,
                'is_company' => true,
            ],
        ])->merge($advisors->map(function ($advisor) use ($formatFullName) {
            $advisorName = $formatFullName($advisor);
            $advisorCommission = (float) ($advisor->pivot->commission_amount ?? 0);
            $advisorPctOfOperation = (float) ($advisor->pivot->commission_percentage ?? 0);

            return [
                'name' => $advisorName,
                'ci' => $advisor->ci ?? 'N/D',
                'email' => $advisor->email ?? null,
                'amount' => $advisorCommission,
                'share_percentage' => $advisorPctOfOperation > 0 ? round(($advisorPctOfOperation / 5) * 100, 2) : 0,
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
                <div>C.I. {{ $recipientCi }}</div>
            </div>
        </div>
    @endforeach
</body>
</html>
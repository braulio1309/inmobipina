<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $property->title }} - Inmobipina</title>
    <link rel="shortcut icon" href="{{ url(config('settings.application.company_icon')) }}" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #F9F9F9;
            color: #333;
            line-height: 1.6;
        }

        .header {
            background-color: #337300;
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .logo-section {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo {
            max-height: 60px;
            max-width: 200px;
        }

        .content {
            background: white;
            margin: 30px auto;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .property-title {
            font-size: 2rem;
            color: #337300;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .property-price {
            font-size: 1.8rem;
            color: #337300;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .property-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
            padding: 20px;
            background-color: #E8F5E3;
            border-radius: 8px;
        }

        .detail-item {
            text-align: center;
        }

        .detail-label {
            font-size: 0.9rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .detail-value {
            font-size: 1.3rem;
            color: #337300;
            font-weight: 600;
        }

        .property-description {
            margin: 30px 0;
        }

        .property-description h3 {
            color: #337300;
            margin-bottom: 15px;
            font-size: 1.4rem;
        }

        .property-description p {
            color: #555;
            line-height: 1.8;
            font-size: 1rem;
        }

        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            margin: 5px;
        }

        .badge-success {
            background-color: #337300;
            color: white;
        }

        .badge-info {
            background-color: #38a4f8;
            color: white;
        }

        .badge-warning {
            background-color: #EB5C00;
            color: white;
        }

        .badge-primary {
            background-color: #4466F2;
            color: white;
        }

        .property-address {
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-left: 4px solid #337300;
            border-radius: 4px;
        }

        .property-address strong {
            color: #337300;
        }

        .footer {
            text-align: center;
            padding: 30px 0;
            color: #666;
            font-size: 0.9rem;
        }

        .type-badges {
            margin: 20px 0;
        }

        @media (max-width: 768px) {
            .property-title {
                font-size: 1.5rem;
            }

            .property-price {
                font-size: 1.4rem;
            }

            .property-details {
                grid-template-columns: 1fr;
            }

            .content {
                padding: 20px;
                margin: 20px auto;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="logo-section">
                @if(filter_var(config('settings.application.company_logo'), FILTER_VALIDATE_URL))
                    <img src="{{ config('settings.application.company_logo') }}" alt="Inmobipina" class="logo">
                @else
                    <img src="{{ url(config('settings.application.company_logo')) }}" alt="Inmobipina" class="logo">
                @endif
            </div>
        </div>
    </div>

    <div class="container">
        <div class="content">
            <h1 class="property-title">{{ $property->title }}</h1>
            
            <div class="property-price">
                ${{ number_format($property->price, 2) }}
            </div>

            <div class="type-badges">
                @if($property->type)
                    @php
                        $typeClass = 'primary';
                        if ($property->type == 'Casa') $typeClass = 'warning';
                        if ($property->type == 'Terreno') $typeClass = 'success';
                        if ($property->type == 'Galpon') $typeClass = 'info';
                        if ($property->type == 'Local') $typeClass = 'primary';
                    @endphp
                    <span class="badge badge-{{ $typeClass }}">{{ $property->type }}</span>
                @endif

                @if($property->type_sale)
                    @php
                        $saleClass = 'primary';
                        if ($property->type_sale == 'alquiler') $saleClass = 'info';
                        if ($property->type_sale == 'venta') $saleClass = 'success';
                    @endphp
                    <span class="badge badge-{{ $saleClass }}">{{ ucfirst($property->type_sale) }}</span>
                @endif

                @if($property->exclusivity)
                    <span class="badge badge-warning">Exclusivo</span>
                @endif
            </div>

            <div class="property-address">
                <strong>📍 Ubicación:</strong> {{ $property->address }}
            </div>

            <div class="property-details">
                @if($property->bedrooms)
                <div class="detail-item">
                    <div class="detail-label">Dormitorios</div>
                    <div class="detail-value">{{ $property->bedrooms }}</div>
                </div>
                @endif

                @if($property->bathrooms)
                <div class="detail-item">
                    <div class="detail-label">Baños</div>
                    <div class="detail-value">{{ $property->bathrooms }}</div>
                </div>
                @endif

                @if($property->square_meters)
                <div class="detail-item">
                    <div class="detail-label">Superficie</div>
                    <div class="detail-value">{{ $property->square_meters }} m²</div>
                </div>
                @endif
            </div>

            @if($property->description)
            <div class="property-description">
                <h3>Descripción</h3>
                <p>{{ $property->description }}</p>
            </div>
            @endif
        </div>
    </div>

    <div class="footer">
        <div class="container">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>

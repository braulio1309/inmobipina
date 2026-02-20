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

        /* Carousel styles */
        .carousel {
            position: relative;
            width: 100%;
            overflow: hidden;
            border-radius: 8px;
            margin-bottom: 30px;
            background: #000;
        }

        .carousel-inner {
            display: flex;
            transition: transform 0.4s ease;
        }

        .carousel-item {
            min-width: 100%;
            position: relative;
        }

        .carousel-item img {
            width: 100%;
            height: 450px;
            object-fit: cover;
            display: block;
        }

        .carousel-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0,0,0,0.5);
            color: white;
            border: none;
            padding: 14px 18px;
            cursor: pointer;
            font-size: 1.4rem;
            z-index: 10;
            border-radius: 4px;
        }

        .carousel-btn:hover {
            background: rgba(0,0,0,0.8);
        }

        .carousel-btn-prev { left: 10px; }
        .carousel-btn-next { right: 10px; }

        .carousel-indicators {
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 8px;
        }

        .carousel-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(255,255,255,0.5);
            cursor: pointer;
            border: none;
        }

        .carousel-dot.active {
            background: white;
        }

        .carousel-counter {
            position: absolute;
            top: 10px;
            right: 14px;
            background: rgba(0,0,0,0.5);
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
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

            .carousel-item img {
                height: 240px;
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

            {{-- Image carousel --}}
            @if($property->images && $property->images->count() > 0)
            <div class="carousel" id="propertyCarousel">
                <div class="carousel-inner" id="carouselInner">
                    @foreach($property->images as $image)
                    <div class="carousel-item">
                        <img src="{{ asset('storage/' . $image->path) }}" alt="{{ $property->title }}">
                    </div>
                    @endforeach
                </div>

                @if($property->images->count() > 1)
                <button class="carousel-btn carousel-btn-prev" onclick="carouselPrev()" aria-label="Imagen anterior">&#10094;</button>
                <button class="carousel-btn carousel-btn-next" onclick="carouselNext()" aria-label="Imagen siguiente">&#10095;</button>

                <div class="carousel-indicators" id="carouselIndicators">
                    @foreach($property->images as $i => $image)
                    <button class="carousel-dot {{ $i === 0 ? 'active' : '' }}" onclick="goToSlide({{ $i }})" aria-label="Ir a imagen {{ $i + 1 }}"></button>
                    @endforeach
                </div>

                <div class="carousel-counter" id="carouselCounter">
                    1 / {{ $property->images->count() }}
                </div>
                @endif
            </div>
            @endif

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

    @if($property->images && $property->images->count() > 1)
    <script>
        var currentSlide = 0;
        var totalSlides = {{ $property->images->count() }};

        function updateCarousel() {
            document.getElementById('carouselInner').style.transform = 'translateX(-' + (currentSlide * 100) + '%)';
            document.getElementById('carouselCounter').textContent = (currentSlide + 1) + ' / ' + totalSlides;
            var dots = document.querySelectorAll('.carousel-dot');
            dots.forEach(function(dot, i) {
                dot.classList.toggle('active', i === currentSlide);
            });
        }

        function carouselNext() {
            currentSlide = (currentSlide + 1) % totalSlides;
            updateCarousel();
        }

        function carouselPrev() {
            currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
            updateCarousel();
        }

        function goToSlide(index) {
            currentSlide = index;
            updateCarousel();
        }

        // Auto-advance every 5 seconds
        setInterval(carouselNext, 5000);
    </script>
    @endif
</body>
</html>

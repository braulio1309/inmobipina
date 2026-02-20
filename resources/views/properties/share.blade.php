<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $property->title }} - Inmobipina</title>
    <link rel="shortcut icon" href="{{ url(config('settings.application.company_icon')) }}" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #ffffff;
            color: #333;
            line-height: 1.6;
        }

        /* Cabecera Minimalista */
        .header {
            background-color: #ffffff;
            padding: 20px 0;
            border-bottom: 1px solid #eaeaea;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .logo-section {
            display: flex;
            align-items: center;
            justify-content: flex-start; /* Alineado a la izquierda para más elegancia */
        }

        .logo {
            max-height: 45px;
            max-width: 200px;
            object-fit: contain;
        }

        /* Galería Panorámica (Carrusel) */
        .property-gallery {
            position: relative;
            margin: 30px auto;
            border-radius: 20px;
            overflow: hidden;
            height: 550px;
            background-color: #f8f8f8;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
        }

        .carousel {
            position: relative;
            width: 100%;
            height: 100%;
        }

        .carousel-inner {
            display: flex;
            height: 100%;
            transition: transform 0.5s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        .carousel-item {
            min-width: 100%;
            height: 100%;
            position: relative;
        }

        .carousel-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .carousel-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.9);
            color: #333;
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.2rem;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
            transition: all 0.2s ease;
        }

        .carousel-btn:hover {
            background: #fff;
            transform: translateY(-50%) scale(1.05);
        }

        .carousel-btn-prev { left: 20px; }
        .carousel-btn-next { right: 20px; }

        .share-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: #fff;
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
            cursor: pointer;
            z-index: 10;
            transition: transform 0.2s ease;
        }

        .share-btn:hover {
            transform: scale(1.05);
        }

        /* Contenido Principal de la Propiedad */
        .property-info {
            max-width: 900px;
            margin: 0 auto 50px auto;
            padding: 0 20px;
        }

        .features-row {
            display: flex;
            align-items: center;
            gap: 25px;
            margin-bottom: 15px;
            color: #444;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .feature-item svg {
            width: 22px;
            height: 22px;
            fill: none;
            stroke: #444;
            stroke-width: 1.5;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .property-title {
            font-size: 2.2rem;
            color: #111;
            font-weight: 700;
            margin-bottom: 5px;
            line-height: 1.2;
            letter-spacing: -0.5px;
        }

        .property-address {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 25px;
            font-weight: 400;
        }

        .price-badge-row {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 35px;
        }

        .property-price {
            font-size: 2.4rem;
            color: #111;
            font-weight: 700;
            letter-spacing: -1px;
        }

        .badge-ref {
            background-color: #E8F5E3; /* Verde muy sutil */
            color: #337300; /* Tu verde principal */
            padding: 8px 18px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
        }

        /* Etiquetas secundarias */
        .tags-row {
            margin-bottom: 30px;
        }
        
        .badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-right: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge-type { background-color: #f4f4f4; color: #444; }
        .badge-sale { background-color: #337300; color: #fff; } 
        .badge-exclusive { background-color: #111; color: #fff; }

        .property-description {
            color: #555;
            font-size: 1.05rem;
            line-height: 1.8;
            white-space: pre-line;
            padding-top: 20px;
            border-top: 1px solid #eaeaea;
        }

        .footer {
            text-align: center;
            padding: 30px 0;
            color: #888;
            font-size: 0.9rem;
            border-top: 1px solid #eaeaea;
        }

        @media (max-width: 900px) {
            .property-gallery {
                height: 350px;
                border-radius: 12px;
                margin: 20px auto;
            }
            .property-title {
                font-size: 1.8rem;
            }
            .property-price {
                font-size: 1.8rem;
            }
            .features-row {
                flex-wrap: wrap;
                gap: 15px;
            }
            .logo-section {
                justify-content: center; /* Centrar logo en móviles */
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="logo-section">
                @if(filter_var(config('settings.application.company_logo'), FILTER_VALIDATE_URL))
                    <img src="{{ config('settings.application.company_logo') }}" alt="Inmobipina" class="logo">
                @else
                    <img src="{{ url(config('settings.application.company_logo')) }}" alt="Inmobipina" class="logo">
                @endif
            </div>
        </div>
    </header>

    <div class="container">
        <div class="property-gallery">
            <button class="share-btn" aria-label="Compartir">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="18" cy="5" r="3"></circle>
                    <circle cx="6" cy="12" r="3"></circle>
                    <circle cx="18" cy="19" r="3"></circle>
                    <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
                    <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
                </svg>
            </button>

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
                <button class="carousel-btn carousel-btn-prev" onclick="carouselPrev()" aria-label="Anterior">&#10094;</button>
                <button class="carousel-btn carousel-btn-next" onclick="carouselNext()" aria-label="Siguiente">&#10095;</button>
                @endif
            </div>
            @endif
        </div>
    </div>

    <div class="property-info">
        
        <div class="features-row">
            @if($property->bathrooms)
            <div class="feature-item">
                <svg viewBox="0 0 24 24"><path d="M7 2v10H3V2h4zm14 10V2h-4v10h4zM4 14a2 2 0 0 0-2 2v4h2v-2h16v2h2v-4a2 2 0 0 0-2-2H4z"></path></svg>
                <span>{{ $property->bathrooms }} baño</span>
            </div>
            @endif

            @if($property->bedrooms)
            <div class="feature-item">
                <svg viewBox="0 0 24 24"><path d="M3 14c0 1.1.9 2 2 2h14a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v4z"></path><path d="M7 10V7a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v3"></path></svg>
                <span>{{ $property->bedrooms }} dor.</span>
            </div>
            @endif

            @if($property->square_meters)
            <div class="feature-item">
                <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9z"></path></svg>
                <span>{{ $property->square_meters }}m²</span>
            </div>
            @endif
        </div>

        <h1 class="property-title">{{ $property->title }}</h1>
        <p class="property-address">{{ $property->address }}</p>

        <div class="price-badge-row">
            <div class="property-price">
                ${{ number_format($property->price, 2) }}
            </div>
            <div class="badge-ref">
                Exp. #{{ str_pad($property->id, 4, '0', STR_PAD_LEFT) }}
            </div>
        </div>

        <div class="tags-row">
            @if($property->type)
                <span class="badge badge-type">{{ $property->type }}</span>
            @endif
            @if($property->type_sale)
                <span class="badge badge-sale">{{ ucfirst($property->type_sale) }}</span>
            @endif
            @if($property->exclusivity)
                <span class="badge badge-exclusive">Exclusivo</span>
            @endif
        </div>

        @if($property->description)
        <div class="property-description">
            {{ $property->description }}
        </div>
        @endif
    </div>

    <footer class="footer">
        <div class="container">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.</p>
        </div>
    </footer>

    @if($property->images && $property->images->count() > 1)
    <script>
        var currentSlide = 0;
        var totalSlides = {{ $property->images->count() }};

        function updateCarousel() {
            document.getElementById('carouselInner').style.transform = 'translateX(-' + (currentSlide * 100) + '%)';
        }

        function carouselNext() {
            currentSlide = (currentSlide + 1) % totalSlides;
            updateCarousel();
        }

        function carouselPrev() {
            currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
            updateCarousel();
        }
    </script>
    @endif
</body>
</html>
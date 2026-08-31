<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>{{ $contacto->nombre }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Raleway', 'Arial', sans-serif;
            background: #f7f7f7;
        }

        .bg-gradient-diagonal {
            background: linear-gradient(45deg, #253746 0%, #253746 100%);
        }

        .card-wrapper {
            max-width: 450px;
            width: 100%;
            margin: 0 auto;
            box-shadow: 0 -5px 40px 7px rgba(0, 0, 0, 0.08);
        }

        .img-wrap {
            padding-top: 50px;
            padding-bottom: 10px;
            text-align: center;
        }

        .img-body {
            border-radius: 50%;
            height: 95px;
            width: 95px;
            object-fit: cover;
            border: 2px solid #fff;
            box-shadow: 0 0 20px 5px rgba(0, 0, 0, 0.2);
        }

        .img-placeholder {
            border-radius: 50%;
            height: 95px;
            width: 95px;
            border: 2px solid #fff;
            box-shadow: 0 0 20px 5px rgba(0, 0, 0, 0.2);
            background: rgba(255, 255, 255, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            color: #fff;
            font-size: 32px;
            font-weight: bold;
        }

        .card-title {
            color: #fff;
            text-shadow: 1px 1px 1px rgba(0, 0, 0, 0.4);
            font-size: 1.4rem;
            font-weight: 700;
            margin: 0;
        }

        .card-subtitle {
            color: #fff;
            font-size: 0.85rem;
            margin: 4px 0;
        }

        .card-body-header {
            text-align: center;
            padding: 8px 20px 20px;
        }

        .vcard-functions {
            display: flex;
            width: 100%;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            background: linear-gradient(45deg, #e40037 0%, #c03e4a 100%);
        }

        .vcard-functions a {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            color: #fff;
            padding: 12px 0;
            border-right: 1px solid rgba(255, 255, 255, 0.25);
            text-decoration: none;
            font-size: 12px;
        }

        .vcard-functions a:last-child {
            border-right: none;
        }

        .vcard-functions a:hover {
            background: rgba(0, 0, 0, 0.15);
            color: #fff;
        }

        .vcard-functions svg {
            fill: #fff;
        }

        .vcard-functions img {
            width: 22px;
            height: 22px;
            object-fit: contain;
        }

        .vcard-body-wrapper {
            max-width: 450px;
            margin: 0 auto;
            background: #fff;
        }

        .vcard-body {
            padding: 20px 30px 30px;
        }

        .table-row {
            position: relative;
            padding: 18px 0 18px 55px;
            border-bottom: 1px solid #f0f0f0;
        }

        .table-row:last-of-type {
            border-bottom: none;
        }

        .table-row svg,
        .table-row img {
            position: absolute;
            top: 22px;
            left: 10px;
            width: 18px;
            /* Forzamos el ancho a 18px */
            height: 18px;
            /* Forzamos el alto a 18px */
            object-fit: contain;
            /* Evita que la imagen se deforme */
        }

        .table-row small {
            display: block;
            color: #b3b4bb;
            font-size: 12px;
            margin-bottom: 4px;
        }

        .table-row h5 {
            margin: 0;
            font-size: 1rem;
            color: #222;
        }

        .table-row a {
            color: #c03e4a;
            text-decoration: none;
            word-break: break-all;
        }

        .table-row a:hover {
            text-decoration: underline;
        }

        .map-wrap {
            padding: 0 0 18px;
            border-bottom: 1px solid #f0f0f0;
        }

        .map-wrap iframe {
            border-radius: 6px;
            display: block;
        }

        .btn-vcard {
            width: 100%;
            border: none;
            color: #fff;
            padding: 14px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 20px;
        }

        .btn-vcard:hover {
            color: #fff;
            opacity: 0.92;
        }
    </style>
</head>

<body>

    <div class="card-wrapper bg-gradient-diagonal">
        <div class="img-wrap">
            @if ($contacto->foto_url)
                <img src="{{ $contacto->foto_url }}" alt="{{ $contacto->nombre }}" class="img-body">
            @else
                <div class="img-placeholder">{{ strtoupper(substr($contacto->nombre, 0, 1)) }}</div>
            @endif
        </div>

        <div class="card-body-header">
            <h2 class="card-title">{{ $contacto->nombre }}</h2>
            <p class="card-subtitle">{{ $contacto->cargo }}</p>
            <p class="card-subtitle">{{ config('comercial.empresa') }}</p>
        </div>

        <div class="vcard-functions">
            <a href="{{ config('comercial.facebook') }}" target="_blank" rel="noopener">
                <img src="{{ asset('images/social/facebook.png') }}" alt="Facebook">
                <span>Facebook</span>
            </a>
            <a href="{{ config('comercial.instagram') }}" target="_blank" rel="noopener">
                <img src="{{ asset('images/social/instagram.png') }}" alt="Instagram">
                <span>Instagram</span>
            </a>
            <a href="{{ config('comercial.tiktok') }}" target="_blank" rel="noopener">
                <img src="{{ asset('images/social/tiktok.png') }}" alt="WhatsApp">
                <span>TikTok</span>
            </a>
            <a href="{{ $contacto->whatsapp_url }}" target="_blank" rel="noopener">
                <img src="{{ asset('images/social/whatsapp.png') }}" alt="TikTok">
                <span>WhatsApp</span>
            </a>
        </div>
    </div>

    <div class="vcard-body-wrapper">
        <div class="vcard-body">

            <div class="table-row">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24">
                    <path
                        d="M20 22.621l-3.521-6.795c-.008.004-1.974.97-2.064 1.011-2.24 1.086-6.799-7.82-4.609-8.994l2.083-1.026-3.493-6.817-2.106 1.039c-7.202 3.755 4.233 25.982 11.6 22.615.121-.055 2.102-1.029 2.11-1.033z" />
                </svg>
                <small>Telefono</small>
                <a href="tel:{{ $contacto->telefono }}">
                    <h5>{{ $contacto->telefono }}</h5>
                </a>
            </div>

            <div class="table-row">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24">
                    <path
                        d="M12 12.713l-11.985-9.713h23.97l-11.985 9.713zm0 2.574l-12-9.725v15.438h24v-15.438l-12 9.725z" />
                </svg>
                <small>Email</small>
                <a href="mailto:{{ $contacto->email }}">
                    <h5>{{ $contacto->email }}</h5>
                </a>
            </div>

            @if ($contacto->agencia)
                <div class="table-row">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24">
                        <path
                            d="M12 0c-4.198 0-8 3.403-8 7.602 0 4.198 3.469 9.21 8 16.398 4.531-7.188 8-12.2 8-16.398 0-4.199-3.801-7.602-8-7.602zm0 11c-1.657 0-3-1.343-3-3s1.343-3 3-3 3 1.343 3 3-1.343 3-3 3z" />
                    </svg>
                    <small>Direccion</small>
                    <h5>{{ $contacto->agencia->descripcion }}:
                        {{ $contacto->agencia->direccion }}<br>{{ $contacto->agencia->ciudad }}, Bolivia</h5>
                </div>
            @endif

            <div class="table-row">
                <img src="{{ asset('images/social/web.png') }}" alt="Pagina Web">
                <small>Pagina Web</small>
                <a href="{{ config('comercial.website') }}" target="_blank">
                    <h5>{{ config('comercial.website') }}</h5>
                </a>
            </div>

            <button style="background: linear-gradient(45deg, #e40037 0%, #c03e4a 100%);" class="btn-vcard"
                onclick="window.location.href='{{ route('tarjeta.vcard', $contacto->uuid) }}'">
                <svg xmlns="http://www.w3.org/2000/svg" fill="white" width="18" height="18"
                    viewBox="0 0 24 24">
                    <path
                        d="M19.5 15c-2.483 0-4.5 2.015-4.5 4.5s2.017 4.5 4.5 4.5 4.5-2.015 4.5-4.5-2.017-4.5-4.5-4.5zm2.5 5h-2v2h-1v-2h-2v-1h2v-2h1v2h2v1zm-7.18 4h-14.815l-.005-1.241c0-2.52.199-3.975 3.178-4.663 3.365-.777 6.688-1.473 5.09-4.418-4.733-8.729-1.35-13.678 3.732-13.678 6.751 0 7.506 7.595 3.64 13.679-1.292 2.031-2.64 3.63-2.64 5.821 0 1.747.696 3.331 1.82 4.5z" />
                </svg>
                Descargar Vcard
            </button>

        </div>
    </div>

</body>

</html>

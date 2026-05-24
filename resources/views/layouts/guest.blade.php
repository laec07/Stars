<!DOCTYPE html>
<html lang="en">

<head>
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta content='width=device-width, initial-scale=1.0, shrink-to-fit=no' name='viewport' />
    <meta name="_token" content="{{ csrf_token() }}" url="{{ url('/') }}" />
    <title>{{$appearance->app_name}} | Admin</title>
    {{-- Favicon: prioriza el configurado desde admin; los de marca van como fallback estándar --}}
    <link rel="shortcut icon" href="{{url($appearance->icon)}}">
    <link rel="icon" href="{{ dsAsset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ dsAsset('img/brand/favicon-32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ dsAsset('img/brand/favicon-16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ dsAsset('img/brand/favicon-180.png') }}">

    <!-- Fonts and icons -->
    <script src="{{ dsAsset('js/lib/assets/js/plugin/webfont/webfont.min.js') }}"></script>
    <script>
        WebFont.load({
            google: {
                "families": ["Lato:300,400,700,900"]
            },
            custom: {
                "families": ["Flaticon", "Font Awesome 5 Solid", "Font Awesome 5 Regular",
                    "Font Awesome 5 Brands", "simple-line-icons"
                ],
                urls: ["{{ url('/') }}/js/lib/assets/css/fonts.min.css"]
            },
            active: function() {
                sessionStorage.fonts = true;
            }
        });

    </script>

    <!-- CSS Files -->
    <link href="{{ dsAsset('js/lib/assets/css/atlantis.min.css') }}" rel="stylesheet" />
    <link href="{{ dsAsset('css/site.css') }}" rel="stylesheet" />
    <link href="{{ dsAsset('js/lib/assets/css/bootstrap.min.css') }}" rel="stylesheet" />
    {{-- Fase 5 — Branding Healing Hands. Después de atlantis/site para override. --}}
    <link href="{{ dsAsset('css/brand.css?v=1') }}" rel="stylesheet" />
    <script src="{{ dsAsset('js/lib/assets/js/core/jquery-3.6.0.min.js') }}"></script>

</head>

<body>

    <div class="content">
        @yield('content')
    </div>




    <!--   Core JS Files   -->
    <script src="{{ dsAsset('js/lib/assets/js/core/bootstrap.min.js') }}"></script>
</body>

</html>

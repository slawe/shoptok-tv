<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Televizorji')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous"
    >
    <style>
        body {
            background-color: #f5f5f5;
        }

        .product-card img {
            object-fit: contain;
            max-height: 160px;
        }

        .product-title {
            font-size: 0.95rem;
            font-weight: 500;
            min-height: 3em;
        }

        .price {
            font-size: 1.1rem;
            font-weight: 700;
        }

        .shop-label {
            font-size: 0.85rem;
            color: #6c757d;
        }
    </style>

    @stack('head')
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="{{ route('tv.index') }}">
            Shoptok TV (demo)
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar"
                aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a
                        class="nav-link {{ request()->routeIs('tv.index') ? 'active' : '' }}"
                        href="{{ route('tv.index') }}"
                    >
                        Televizorji
                    </a>
                </li>
                <li class="nav-item">
                    <a
                        class="nav-link {{ request()->routeIs('tv.receivers') ? 'active' : '' }}"
                        href="{{ route('tv.receivers') }}"
                    >
                        TV sprejemniki
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<main class="container mb-5">
    @yield('content')
</main>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe"
    crossorigin="anonymous"
></script>
@stack('scripts')
</body>
</html>

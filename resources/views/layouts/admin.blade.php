<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Panel de Sorteos - {{ config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="bg-gray-100">
    <!-- Navegación -->
    <nav class="bg-white shadow-sm">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <a href="{{ route('draws.index') }}" class="text-xl font-bold text-gray-800">
                        🎲 Sistema de Sorteos
                    </a>
                </div>

                <div class="flex items-center space-x-4">
                    <a href="{{ route('draws.index') }}" class="text-gray-600 hover:text-gray-900">
                        Sorteos
                    </a>
                    <a href="{{ route('draws.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        + Nuevo Sorteo
                    </a>

                    @auth
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-gray-600 hover:text-gray-900">
                            Cerrar Sesión
                        </button>
                    </form>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Contenido principal -->
    <main class="min-h-screen py-6">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-12">
        <div class="container mx-auto px-4 py-6">
            <div class="text-center text-gray-600 text-sm">
                © {{ date('Y') }} Sistema de Sorteos. Todos los derechos reservados.
            </div>
        </div>
    </footer>
</body>

</html>

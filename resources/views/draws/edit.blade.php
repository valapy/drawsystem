@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <a href="{{ route('draws.show', $draw) }}" class="text-blue-600 hover:text-blue-800">
            ← Volver al Sorteo
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h1 class="text-3xl font-bold mb-6">Editar Sorteo</h1>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if(session('warning'))
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
                {{ session('warning') }}
                @if(session('needs_confirmation'))
                    <p class="mt-2 font-bold">¿Deseas continuar de todas formas?</p>
                @endif
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Tabs -->
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-8 gap-3">
                <button onclick="showTab('info')" id="tab-info" class="cursor-pointer tab-button border-b-2 border-blue-500 text-blue-600 py-4 px-1 font-medium">
                    Información Básica
                </button>
                <button onclick="showTab('image')" id="tab-image" class="cursor-pointer tab-button border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 py-4 px-1 font-medium">
                    Imagen de Fondo
                </button>
                <button onclick="showTab('excel')" id="tab-excel" class="cursor-pointer tab-button border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 py-4 px-1 font-medium">
                    Participantes (Excel)
                </button>
                <button onclick="showTab('display')" id="tab-display" class="cursor-pointer tab-button border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 py-4 px-1 font-medium">
                    Campos a Mostrar
                </button>
            </nav>
        </div>

        <!-- Tab: Información Básica -->
        <div id="content-info" class="tab-content">
            <h2 class="text-xl font-bold mb-4">Información Básica</h2>
            <form action="{{ route('draws.update', $draw) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label class="block text-gray-700 font-bold mb-2">Nombre del Sorteo</label>
                    <input type="text" name="name" value="{{ old('name', $draw->name) }}" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 font-bold mb-2">Estado</label>
                    <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                        <option value="active" {{ $draw->status === 'active' ? 'selected' : '' }}>Activo</option>
                        <option value="finished" {{ $draw->status === 'finished' ? 'selected' : '' }}>Finalizado</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 font-bold mb-2">Estadísticas</label>
                    <div class="grid grid-cols-3 gap-4">
                        <div class="bg-blue-50 p-4 rounded">
                            <div class="text-sm text-blue-600">Participantes</div>
                            <div class="text-2xl font-bold">{{ $draw->participants->count() }}</div>
                        </div>
                        <div class="bg-green-50 p-4 rounded">
                            <div class="text-sm text-green-600">Ganadores</div>
                            <div class="text-2xl font-bold">{{ $draw->winners->count() }}</div>
                        </div>
                        <div class="bg-purple-50 p-4 rounded">
                            <div class="text-sm text-purple-600">Disponibles</div>
                            <div class="text-2xl font-bold">{{ $draw->participants->count() - $draw->winners->count() }}</div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Guardar Cambios
                </button>
            </form>
        </div>

        <!-- Tab: Imagen de Fondo -->
        <div id="content-image" class="tab-content hidden">
            <h2 class="text-xl font-bold mb-4">Imagen de Fondo</h2>

            @if($draw->background_image)
                <div class="mb-4">
                    <label class="block text-gray-700 font-bold mb-2">Imagen Actual</label>
                    <img src="{{ asset('storage/' . $draw->background_image) }}" alt="Background" class="max-w-md rounded-lg shadow-md">
                </div>
            @else
                <p class="text-gray-600 mb-4">No hay imagen de fondo. Se usa el degradado predeterminado.</p>
            @endif

            <form action="{{ route('draws.update-image', $draw) }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-4">
                    <label class="block text-gray-700 font-bold mb-2">Nueva Imagen</label>
                    <input type="file" name="background_image" accept="image/*" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <p class="text-gray-500 text-sm mt-1">Recomendado: 1920x1080px, máximo 5MB</p>
                </div>

                <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    Actualizar Imagen
                </button>

                @if($draw->background_image)
                    <a href="{{ route('draws.update', $draw) }}" onclick="event.preventDefault(); if(confirm('¿Eliminar imagen de fondo?')) document.getElementById('delete-image-form').submit();"
                       class="ml-2 px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 inline-block">
                        Eliminar Imagen
                    </a>
                @endif
            </form>

            @if($draw->background_image)
                <form id="delete-image-form" action="{{ route('draws.update', $draw) }}" method="POST" class="hidden">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="remove_background" value="1">
                </form>
            @endif
        </div>

        <!-- Tab: Participantes (Excel) -->
        <div id="content-excel" class="tab-content hidden">
            <h2 class="text-xl font-bold mb-4">Actualizar Participantes</h2>

            <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-4">
                <p class="text-yellow-700">
                    <strong>⚠️ Advertencia:</strong> Subir un nuevo Excel reemplazará todos los participantes actuales.
                </p>
            </div>

            <form action="{{ route('draws.update-excel', $draw) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @if(session('needs_confirmation'))
                    <input type="hidden" name="confirmed" value="1">
                @endif

                <div class="mb-4">
                    <label class="block text-gray-700 font-bold mb-2">Nuevo Excel</label>
                    <input type="file" name="excel_file" accept=".xlsx,.xls,.csv" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>

                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="keep_winners" value="1" class="mr-2">
                        <span class="text-gray-700">Intentar mantener ganadores (busca por cédula/email/nombre)</span>
                    </label>
                </div>

                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4">
                    <p class="text-blue-700 text-sm">
                        <strong>Campos actuales:</strong> {{ implode(', ', $draw->available_fields) }}
                    </p>
                    <p class="text-blue-700 text-sm mt-2">
                        El nuevo Excel debe tener la misma estructura o se mostrarán advertencias.
                    </p>
                </div>

                <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700">
                    Actualizar Participantes
                </button>
            </form>
        </div>

        <!-- Tab: Campos a Mostrar -->
        <div id="content-display" class="tab-content hidden">
            <h2 class="text-xl font-bold mb-4">Cambiar Campos a Mostrar</h2>

            <form action="{{ route('draws.update', $draw) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label class="block text-gray-700 font-bold mb-2">Campos Disponibles</label>
                    <p class="text-gray-600 text-sm mb-3">
                        Selecciona los campos que se mostrarán durante el sorteo. Se mostrarán en el orden seleccionado.
                    </p>

                    @php
                        // Extraer campos del display_template
                        $currentDisplayFields = [];
                        if ($draw->display_template) {
                            // Extraer campos de {campo1} {campo2}
                            preg_match_all('/\{([^}]+)\}/', $draw->display_template, $matches);
                            $currentDisplayFields = $matches[1] ?? [];
                        } elseif ($draw->display_field) {
                            $currentDisplayFields = is_array($draw->display_field)
                                ? $draw->display_field
                                : [$draw->display_field];
                        }
                    @endphp

                    <div class="space-y-2">
                        @foreach($draw->available_fields as $field)
                            <label class="flex items-center space-x-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                                <input type="checkbox" name="display_fields[]" value="{{ $field }}"
                                       {{ in_array($field, $currentDisplayFields) ? 'checked' : '' }}
                                       class="w-5 h-5 text-blue-600">
                                <span class="text-gray-700">{{ ucfirst(str_replace('_', ' ', $field)) }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4">
                    <p class="text-blue-700 text-sm">
                        <strong>Actual:</strong> {{ $draw->display_template ?? $draw->display_field }}
                    </p>
                </div>

                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Actualizar Campos
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function showTab(tabName) {
    // Ocultar todos los contenidos
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });

    // Remover estilos activos de todos los botones
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('border-blue-500', 'text-blue-600');
        button.classList.add('border-transparent', 'text-gray-500');
    });

    // Mostrar contenido seleccionado
    document.getElementById('content-' + tabName).classList.remove('hidden');

    // Activar botón seleccionado
    const activeButton = document.getElementById('tab-' + tabName);
    activeButton.classList.remove('border-transparent', 'text-gray-500');
    activeButton.classList.add('border-blue-500', 'text-blue-600');
}
</script>
@endsection

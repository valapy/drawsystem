@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-8">
            <h1 class="text-3xl font-bold mb-6">Configurar Sorteo</h1>

            <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
                <p class="text-green-700">
                    ✓ Excel cargado exitosamente: <strong>{{ $total }} participantes</strong>
                </p>
            </div>

            <form action="{{ route('draws.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <!-- Nombre del sorteo -->
                <div class="mb-6">
                    <label class="block text-gray-700 font-bold mb-2">
                        Nombre del Sorteo *
                    </label>
                    <input
                        type="text"
                        name="name"
                        required
                        placeholder="Ej: Sorteo Día del Padre 2025"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                        value="{{ old('name') }}">
                </div>

                <!-- Imagen de fondo -->
                <div class="mb-6">
                    <label class="block text-gray-700 font-bold mb-2">
                        Imagen de Fondo (Opcional)
                    </label>
                    <input
                        type="file"
                        name="background_image"
                        accept="image/*"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    <p class="text-gray-500 text-sm mt-1">
                        Recomendado: 1920x1080px. Si no subes una imagen, se usará un fondo degradado predeterminado.
                    </p>
                </div>

                <!-- Campos detectados -->
                <div class="mb-6">
                    <label class="block text-gray-700 font-bold mb-2">
                        Campos Detectados en el Excel
                    </label>
                    <div class="bg-gray-50 p-4 rounded-lg mb-4">
                        <div class="flex flex-wrap gap-2">
                            @foreach($headers as $header)
                            <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">
                                {{ $header }}
                            </span>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Selección de campos a mostrar -->
                <div class="mb-6">
                    <label class="block text-gray-700 font-bold mb-2">
                        Campos a Mostrar en el Sorteo *
                    </label>
                    <p class="text-gray-600 text-sm mb-3">
                        Selecciona los campos que se mostrarán durante el sorteo. Se mostrarán en el orden que los selecciones.
                    </p>
                    <div class="space-y-2">
                        @foreach($headers as $header)
                        <label class="flex items-center space-x-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                            <input
                                type="checkbox"
                                name="display_fields[]"
                                value="{{ $header }}"
                                class="w-5 h-5 text-blue-600 focus:ring-blue-500">
                            <span class="text-gray-700">{{ ucfirst(str_replace('_', ' ', $header)) }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                <!-- Preview de datos -->
                <div class="mb-6">
                    <label class="block text-gray-700 font-bold mb-2">
                        Vista Previa (Primeros 5 Registros)
                    </label>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white border border-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    @foreach($headers as $header)
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">
                                        {{ $header }}
                                    </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($preview as $row)
                                <tr>
                                    @foreach($headers as $header)
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">
                                        {{ $row[$header] ?? '-' }}
                                    </td>
                                    @endforeach
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex justify-between">
                    <a href="{{ route('draws.create') }}" class="px-6 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition">
                        ← Volver
                    </a>
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        Crear Sorteo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Marcar al menos un campo por defecto (el primero)
    document.addEventListener('DOMContentLoaded', function() {
        const checkboxes = document.querySelectorAll('input[name="display_fields[]"]');
        if (checkboxes.length > 0 && !Array.from(checkboxes).some(cb => cb.checked)) {
            checkboxes[0].checked = true;
        }
    });
</script>
@endsection

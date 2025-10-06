@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <a href="{{ route('draws.index') }}" class="text-blue-600 hover:text-blue-800">
            ← Volver a Sorteos
        </a>
    </div>

    <!-- Header del sorteo -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h1 class="text-3xl font-bold mb-2">{{ $draw->name }}</h1>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600">
                        <strong>Estado:</strong>
                        @if($draw->status === 'active')
                        <span class="text-green-600">Activo</span>
                        @else
                        <span class="text-gray-600">Finalizado</span>
                        @endif
                    </span>
                    <span class="text-gray-600">
                        <strong>Creado:</strong> {{ $draw->created_at->format('d/m/Y H:i') }}
                    </span>
                </div>
            </div>

            <div class="flex space-x-2">
                <a href="{{ route('draws.edit', $draw) }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    ✏️ Editar Sorteo
                </a>
                @if($draw->status === 'active')
                <a href="{{ route('draws.public', $draw) }}" target="_blank" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                    🎬 Proyectar Sorteo
                </a>
                <form action="{{ route('draws.reset', $draw) }}" method="POST" class="inline" onsubmit="return confirm('¿Estás seguro de resetear el sorteo? Se eliminarán todos los ganadores.')">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition">
                        🔄 Resetear
                    </button>
                </form>
                <form action="{{ route('draws.finish', $draw) }}" method="POST" class="inline" onsubmit="return confirm('¿Estás seguro de finalizar el sorteo?')">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
                        ✓ Finalizar
                    </button>
                </form>
                @endif
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="grid grid-cols-3 gap-4 mt-6">
            <div class="bg-blue-50 p-4 rounded-lg">
                <div class="text-sm text-blue-600 font-medium">Total Participantes</div>
                <div class="text-3xl font-bold text-blue-900">{{ $draw->participants->count() }}</div>
            </div>
            <div class="bg-green-50 p-4 rounded-lg">
                <div class="text-sm text-green-600 font-medium">Ganadores</div>
                <div class="text-3xl font-bold text-green-900">{{ $draw->winners->count() }}</div>
            </div>
            <div class="bg-purple-50 p-4 rounded-lg">
                <div class="text-sm text-purple-600 font-medium">Disponibles</div>
                <div class="text-3xl font-bold text-purple-900">{{ $draw->participants->count() - $draw->winners->count() }}</div>
            </div>
        </div>

        <!-- Campos configurados -->
        <div class="mt-6">
            <h3 class="font-bold text-gray-700 mb-2">Campos Disponibles:</h3>
            <div class="flex flex-wrap gap-2">
                @foreach($draw->available_fields as $field)
                <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm">
                    {{ $field }}
                </span>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Ganadores -->
    @if($draw->winners->count() > 0)
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-2xl font-bold mb-4">🏆 Ganadores ({{ $draw->winners->count() }})</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                        @foreach($draw->available_fields as $field)
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ ucfirst(str_replace('_', ' ', $field)) }}
                        </th>
                        @endforeach
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha Ganador</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($draw->winners as $index => $winner)
                    <tr class="hover:bg-yellow-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $index + 1 }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-green-600">
                            {{ $winner->participant->display_value }}
                        </td>
                        @foreach($draw->available_fields as $field)
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $winner->participant->data[$field] ?? '-' }}
                        </td>
                        @endforeach
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $winner->won_at->format('d/m/Y H:i:s') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Botón de exportar ganadores -->
        <div class="mt-4">
            <a href="{{ route('draws.show', $draw) }}?export=winners" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                📥 Exportar Ganadores a Excel
            </a>
        </div>
    </div>
    @endif

    <!-- Todos los participantes -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold mb-4">👥 Todos los Participantes</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                        @foreach($draw->available_fields as $field)
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ ucfirst(str_replace('_', ' ', $field)) }}
                        </th>
                        @endforeach
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($draw->participants as $index => $participant)
                    <tr class="{{ $participant->hasWon() ? 'bg-green-50' : '' }}">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $index + 1 }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $participant->display_value }}
                        </td>
                        @foreach($draw->available_fields as $field)
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $participant->data[$field] ?? '-' }}
                        </td>
                        @endforeach
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            @if($participant->hasWon())
                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">
                                ✓ Ganador
                            </span>
                            @else
                            <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded-full text-xs">
                                Disponible
                            </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

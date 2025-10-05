@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-8">
            <h1 class="text-3xl font-bold mb-6">Crear Nuevo Sorteo</h1>

            @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <ul>
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form action="{{ route('draws.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-6">
                    <label class="block text-gray-700 font-bold mb-2">
                        Paso 1: Subir Excel con Participantes
                    </label>
                    <p class="text-gray-600 text-sm mb-3">
                        Sube un archivo Excel (.xlsx, .xls) o CSV con los datos de los participantes.
                        La primera fila debe contener los nombres de las columnas.
                    </p>
                    <input
                        type="file"
                        name="excel_file"
                        accept=".xlsx,.xls,.csv"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                </div>

                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                <strong>Ejemplo de formato:</strong><br>
                                Nombre | Apellido | Cedula | Codigo_Producto | Telefono<br>
                                Juan | Pérez | 1234567 | ABC123 | 0981234567
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-between">
                    <a href="{{ route('draws.index') }}" class="px-6 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition">
                        Cancelar
                    </a>
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        Continuar →
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

<?php

namespace App\Services;

use App\Models\Draw;
use App\Models\Participant;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\ToCollection;

/**
 * Servicio para importar y procesar archivos Excel de forma dinámica
 *
 * Este servicio permite:
 * 1. Leer cualquier estructura de Excel
 * 2. Detectar columnas automáticamente
 * 3. Normalizar nombres de columnas
 * 4. Guardar participantes con datos flexibles (JSON)
 */
class ExcelImportService
{
    /**
     * PASO 1: Procesar el archivo Excel y extraer los datos
     *
     * @param $file - Archivo subido (UploadedFile)
     * @return array - ['headers' => [...], 'rows' => [...], 'total' => N]
     */
    public function processFile($file): array
    {
        // Usar Laravel Excel para convertir el archivo a Collection
        $data = Excel::toCollection(new class implements ToCollection {
            public function collection(Collection $collection)
            {
                return $collection;
            }
        }, $file)->first(); // ->first() porque puede tener múltiples hojas

        // Primera fila = Headers (nombres de columnas)
        $headers = $data->first()->toArray();

        // Normalizar headers (quitar espacios, acentos, etc.)
        $headers = array_map(fn($h) => $this->normalizeHeader($h), $headers);

        // El resto de filas = Datos
        $rows = $data->skip(1)->map(function ($row) use ($headers) {
            // Combinar headers con valores para crear array asociativo
            // Ejemplo: ['nombre' => 'Juan', 'cedula' => '123456']
            return array_combine($headers, $row->toArray());
        })->toArray();

        return [
            'headers' => $headers,        // ['nombre', 'cedula', 'telefono']
            'rows' => $rows,              // [['nombre' => 'Juan', ...], ...]
            'total' => count($rows),      // Cantidad de participantes
        ];
    }

    /**
     * PASO 2: Normalizar nombre de columna
     *
     * Ejemplos:
     * "Nombre Completo" → "nombre_completo"
     * "Cédula " → "cedula"
     * "Teléfono" → "telefono"
     *
     * @param string $header - Nombre original de la columna
     * @return string - Nombre normalizado
     */
    private function normalizeHeader(string $header): string
    {
        // 1. Convertir a minúsculas
        $normalized = mb_strtolower(trim($header));

        // 2. Reemplazar espacios por guiones bajos
        $normalized = str_replace(' ', '_', $normalized);

        // 3. Quitar acentos y caracteres especiales
        $normalized = $this->removeAccents($normalized);

        // 4. Solo permitir letras, números y guiones bajos
        $normalized = preg_replace('/[^a-z0-9_]/', '', $normalized);

        return $normalized;
    }

    /**
     * PASO 3: Quitar acentos de texto
     *
     * @param string $text
     * @return string
     */
    private function removeAccents(string $text): string
    {
        $accents = [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'Á' => 'a',
            'É' => 'e',
            'Í' => 'i',
            'Ó' => 'o',
            'Ú' => 'u',
            'ñ' => 'n',
            'Ñ' => 'n',
        ];

        return strtr($text, $accents);
    }

    /**
     * PASO 4: Crear el sorteo con todos los participantes
     *
     * @param array $data - Datos del Excel procesados
     * @param array $config - Configuración del sorteo
     * @return Draw - Sorteo creado
     */
    public function createDraw(array $data, array $config): Draw
    {
        // Crear el sorteo
        $draw = Draw::create([
            'name' => $config['name'],                          // "Sorteo Navidad 2025"
            'background_image' => $config['background_image'] ?? null,
            'display_field' => $config['display_field'],        // "nombre"
            'available_fields' => $data['headers'],             // Todos los campos del Excel
            'display_template' => $config['display_template'] ?? null,
            'status' => 'active',
        ]);

        // Crear cada participante
        foreach ($data['rows'] as $row) {
            // Construir el valor que se mostrará en el sorteo
            $displayValue = $this->buildDisplayValue($row, $config);

            Participant::create([
                'draw_id' => $draw->id,
                'data' => $row,                    // TODO el Excel en JSON
                'display_value' => $displayValue,  // "Juan Pérez" (pre-calculado)
            ]);
        }

        return $draw;
    }

    /**
     * PASO 5: Construir el valor a mostrar en el sorteo
     *
     * Si config tiene display_template = "{nombre} {apellido}"
     * Retorna: "Juan Pérez"
     *
     * Si no tiene template, usa display_field
     *
     * @param array $row - Fila del Excel
     * @param array $config - Configuración
     * @return string - Valor a mostrar
     */
    private function buildDisplayValue(array $row, array $config): string
    {
        // Opción 1: Usar template si existe
        if (isset($config['display_template'])) {
            $template = $config['display_template'];

            // Reemplazar {campo} con valores reales
            // "{nombre} {apellido}" → "Juan Pérez"
            foreach ($row as $key => $value) {
                $template = str_replace("{{$key}}", $value, $template);
            }

            return $template;
        }

        // Opción 2: Usar campos específicos
        $fields = is_array($config['display_field'])
            ? $config['display_field']
            : [$config['display_field']];

        $values = [];
        foreach ($fields as $field) {
            if (isset($row[$field]) && !empty($row[$field])) {
                $values[] = $row[$field];
            }
        }

        return implode(' ', $values);
    }

    /**
     * PASO 6: Validar que el archivo tiene datos válidos
     *
     * @param array $data
     * @return bool
     */
    public function validateFile(array $data): bool
    {
        // Debe tener headers
        if (empty($data['headers'])) {
            return false;
        }

        // Debe tener al menos 1 fila de datos
        if (empty($data['rows'])) {
            return false;
        }

        return true;
    }
}

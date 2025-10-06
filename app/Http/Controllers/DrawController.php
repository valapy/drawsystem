<?php

namespace App\Http\Controllers;

use App\Models\Draw;
use App\Models\Participant;
use App\Models\Winner;
use App\Services\ExcelImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class DrawController extends Controller
{
    public function __construct(
        private ExcelImportService $excelService
    ) {}

    /**
     * Panel principal - listado de sorteos
     */
    public function index()
    {
        $draws = Draw::withCount(['participants', 'winners'])
            ->latest()
            ->paginate(20);

        return view('draws.index', compact('draws'));
    }

    /**
     * Formulario para crear nuevo sorteo
     */
    public function create()
    {
        return view('draws.create');
    }

    /**
     * Paso 1: Subir Excel y mostrar preview
     */
    public function uploadExcel(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $file = $request->file('excel_file');
        $data = $this->excelService->processFile($file);

        if (!$this->excelService->validateFile($data)) {
            return back()->withErrors(['excel_file' => 'El archivo no contiene datos válidos']);
        }

        // Guardar temporalmente los datos en sesión
        session(['excel_data' => $data]);

        return view('draws.configure', [
            'headers' => $data['headers'],
            'preview' => array_slice($data['rows'], 0, 5), // Primeras 5 filas
            'total' => $data['total'],
        ]);
    }

    /**
     * Paso 2: Configurar y crear el sorteo
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'display_fields' => 'required|array',
            'display_fields.*' => 'required|string',
            'background_image' => 'nullable|image|max:5120',
        ]);

        $data = session('excel_data');
        if (!$data) {
            return redirect()->route('draws.create')
                ->withErrors(['error' => 'No hay datos del Excel. Por favor, sube el archivo nuevamente.']);
        }

        // Guardar imagen de fondo si existe
        $backgroundPath = null;
        if ($request->hasFile('background_image')) {
            $backgroundPath = $request->file('background_image')
                ->store('backgrounds', 'public');
        }

        // Construir template de display
        $displayFields = $request->input('display_fields');
        $displayTemplate = '{' . implode('} {', $displayFields) . '}';

        $draw = $this->excelService->createDraw($data, [
            'name' => $request->input('name'),
            'background_image' => $backgroundPath,
            'display_field' => $displayFields[0], // Campo principal
            'display_template' => $displayTemplate,
        ]);

        // Limpiar sesión
        session()->forget('excel_data');

        return redirect()->route('draws.show', $draw)
            ->with('success', 'Sorteo creado exitosamente con ' . $data['total'] . ' participantes');
    }

    /**
     * Ver detalles del sorteo
     */
    public function show(Draw $draw, Request $request)
    {
        // Si se solicita exportar ganadores
        if ($request->has('export') && $request->get('export') === 'winners') {
            return Excel::download(
                new \App\Exports\WinnersExport($draw),
                'ganadores_' . Str::slug($draw->name) . '_' . date('Y-m-d') . '.xlsx'
            );
        }

        $draw->load(['participants', 'winners.participant']);

        return view('draws.show', compact('draw'));
    }

    /**
     * Página pública del sorteo (para proyectar)
     */
    public function public(Draw $draw)
    {
        if ($draw->status !== 'active') {
            abort(404);
        }

        $draw->load('winners.participant');
        return view('draws.public', compact('draw'));
    }

    /**
     * API: Realizar sorteo
     */
    public function performDraw(Draw $draw)
    {
        if ($draw->status !== 'active') {
            return response()->json(['error' => 'El sorteo no está activo'], 400);
        }

        $winner = $draw->drawWinner();

        if (!$winner) {
            return response()->json(['error' => 'No hay más participantes disponibles'], 400);
        }

        return response()->json([
            'winner' => [
                'id' => $winner->id,
                'display_value' => $winner->display_value,
                'data' => $winner->data,
            ]
        ]);
    }

    /**
     * API: Obtener todos los participantes para el shuffle
     */
    public function getParticipants(Draw $draw)
    {
        $participants = $draw->availableParticipants()
            ->select('id', 'display_value')
            ->get();

        return response()->json($participants);
    }

    /**
     * Resetear sorteo (eliminar ganadores)
     */
    public function reset(Draw $draw)
    {
        $draw->reset();

        return back()->with('success', 'Sorteo reseteado exitosamente');
    }

    /**
     * Finalizar sorteo
     */
    public function finish(Draw $draw)
    {
        $draw->finish();

        return back()->with('success', 'Sorteo finalizado');
    }

    /**
     * Eliminar sorteo (soft delete)
     */
    public function destroy(Draw $draw)
    {
        $draw->delete();

        return redirect()->route('draws.index')
            ->with('success', 'Sorteo eliminado exitosamente');
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(Draw $draw)
    {
        $draw->load(['participants', 'winners']);
        return view('draws.edit', compact('draw'));
    }


    public function update(Request $request, Draw $draw)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'display_fields' => 'nullable|array',
            'status' => 'nullable|in:active,finished',
            'remove_background' => 'nullable|boolean',
        ]);

        // Eliminar imagen de fondo si se solicita
        if ($request->remove_background) {
            if ($draw->background_image && Storage::disk('public')->exists($draw->background_image)) {
                Storage::disk('public')->delete($draw->background_image);
            }
            $draw->background_image = null;
        }

        // Actualizar nombre
        $draw->name = $request->name;

        // Actualizar campos de visualización si se proporcionan
        if ($request->has('display_fields')) {
            $displayFields = $request->display_fields;
            $draw->display_field = $displayFields[0];
            $draw->display_template = '{' . implode('} {', $displayFields) . '}';

            // Recalcular display_value de todos los participantes
            foreach ($draw->participants as $participant) {
                $values = [];
                foreach ($displayFields as $field) {
                    if (isset($participant->data[$field]) && !empty($participant->data[$field])) {
                        $values[] = $participant->data[$field];
                    }
                }
                $participant->display_value = implode(' ', $values);
                $participant->save();
            }
        }

        // Actualizar estado
        if ($request->has('status')) {
            $draw->status = $request->status;
        }

        $draw->save();

        return redirect()->route('draws.show', $draw)
            ->with('success', 'Sorteo actualizado exitosamente');
    }

    /**
     * Actualizar imagen de fondo
     */
    public function updateImage(Request $request, Draw $draw)
    {
        $request->validate([
            'background_image' => 'required|image|max:5120',
        ]);

        // Eliminar imagen anterior si existe
        if ($draw->background_image && Storage::disk('public')->exists($draw->background_image)) {
            Storage::disk('public')->delete($draw->background_image);
        }

        // Guardar nueva imagen
        $backgroundPath = $request->file('background_image')
            ->store('backgrounds', 'public');

        $draw->background_image = $backgroundPath;
        $draw->save();

        return redirect()->route('draws.edit', $draw)
            ->with('success', 'Imagen de fondo actualizada exitosamente');
    }

    /**
     * Actualizar Excel (reemplazar participantes)
     */
    public function updateExcel(Request $request, Draw $draw)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'keep_winners' => 'nullable|boolean',
        ]);

        $file = $request->file('excel_file');
        $data = $this->excelService->processFile($file);

        if (!$this->excelService->validateFile($data)) {
            return back()->withErrors(['excel_file' => 'El archivo no contiene datos válidos']);
        }

        // Verificar que los headers coincidan con los originales
        $originalFields = $draw->available_fields;
        $newFields = $data['headers'];

        // Advertir si los campos son diferentes
        $missingFields = array_diff($originalFields, $newFields);
        $newFieldsAdded = array_diff($newFields, $originalFields);

        if (!empty($missingFields) || !empty($newFieldsAdded)) {
            $message = 'Advertencia: ';
            if (!empty($missingFields)) {
                $message .= 'Campos faltantes: ' . implode(', ', $missingFields) . '. ';
            }
            if (!empty($newFieldsAdded)) {
                $message .= 'Campos nuevos: ' . implode(', ', $newFieldsAdded) . '.';
            }

            // Si el usuario no confirmó, mostrar advertencia
            if (!$request->has('confirmed')) {
                session(['excel_update_data' => $data, 'excel_update_draw_id' => $draw->id]);
                return back()->with('warning', $message)
                    ->with('needs_confirmation', true);
            }
        }

        // Guardar IDs de ganadores si se desea mantenerlos
        $winnerIdentifiers = [];
        if ($request->keep_winners) {
            $winnerIdentifiers = $draw->winners()
                ->with('participant')
                ->get()
                ->pluck('participant.data')
                ->toArray();
        }

        // Eliminar participantes antiguos
        $draw->participants()->delete();

        // Actualizar campos disponibles
        $draw->available_fields = $data['headers'];
        $draw->save();

        // Crear nuevos participantes
        $newParticipantIds = [];
        foreach ($data['rows'] as $row) {
            $displayValue = $this->buildDisplayValue($row, [
                'display_field' => $draw->display_field,
                'display_template' => $draw->display_template,
            ]);

            $participant = Participant::create([
                'draw_id' => $draw->id,
                'data' => $row,
                'display_value' => $displayValue,
            ]);

            $newParticipantIds[] = $participant->id;
        }

        // Si se debe mantener ganadores, intentar reconstruir la relación
        if ($request->keep_winners && !empty($winnerIdentifiers)) {
            $draw->winners()->delete(); // Eliminar relaciones viejas

            foreach ($winnerIdentifiers as $winnerData) {
                // Buscar participante con datos similares
                $matchingParticipant = Participant::where('draw_id', $draw->id)
                    ->get()
                    ->first(function ($p) use ($winnerData) {
                        // Comparar algunos campos clave
                        $keyFields = ['cedula', 'nombre', 'email'];
                        foreach ($keyFields as $field) {
                            if (isset($winnerData[$field]) && isset($p->data[$field])) {
                                if ($winnerData[$field] === $p->data[$field]) {
                                    return true;
                                }
                            }
                        }
                        return false;
                    });

                if ($matchingParticipant) {
                    Winner::create([
                        'draw_id' => $draw->id,
                        'participant_id' => $matchingParticipant->id,
                        'won_at' => now(),
                    ]);
                }
            }
        } else {
            // Si no se mantienen ganadores, eliminarlos
            $draw->winners()->delete();
        }

        session()->forget(['excel_update_data', 'excel_update_draw_id']);

        return redirect()->route('draws.show', $draw)
            ->with('success', 'Participantes actualizados exitosamente. Total: ' . count($data['rows']));
    }

     /**
     * Helper para construir display value
     */
    private function buildDisplayValue(array $row, array $config): string
    {
        if (isset($config['display_template'])) {
            $template = $config['display_template'];
            foreach ($row as $key => $value) {
                $template = str_replace("{{$key}}", $value, $template);
            }
            return $template;
        }

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
}

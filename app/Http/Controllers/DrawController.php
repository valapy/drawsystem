<?php

namespace App\Http\Controllers;

use App\Models\Draw;
use App\Services\ExcelImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
}

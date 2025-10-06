<?php

namespace App\Exports;

use App\Models\Draw;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WinnersExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $draw;

    public function __construct(Draw $draw)
    {
        $this->draw = $draw;
    }

    /**
     * Obtener la colección de ganadores
     */
    public function collection()
    {
        return $this->draw->winners()
            ->with('participant')
            ->orderBy('won_at', 'asc')
            ->get();
    }

    /**
     * Headers del Excel
     */
    public function headings(): array
    {
        $headers = [
            'Posición',
            'Nombre Mostrado',
            'Fecha/Hora Ganador'
        ];

        // Agregar todos los campos disponibles del sorteo
        foreach ($this->draw->available_fields as $field) {
            $headers[] = ucfirst(str_replace('_', ' ', $field));
        }

        return $headers;
    }

    /**
     * Mapear cada fila
     */
    public function map($winner): array
    {
        static $position = 0;
        $position++;

        $row = [
            $position,
            $winner->participant->display_value,
            $winner->won_at->format('d/m/Y H:i:s'),
        ];

        // Agregar todos los campos del participante
        foreach ($this->draw->available_fields as $field) {
            $row[] = $winner->participant->data[$field] ?? '';
        }

        return $row;
    }

    /**
     * Estilos del Excel
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Estilo del header
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
                ],
                'font' => [
                    'color' => ['rgb' => 'FFFFFF'],
                    'bold' => true,
                ],
            ],
        ];
    }
}

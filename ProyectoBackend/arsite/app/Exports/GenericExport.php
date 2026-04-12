<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GenericExport implements FromCollection, WithHeadings, WithMapping, WithColumnWidths, WithStyles
{
    protected $collection;
    protected $headings;
    protected $mappingCallback;
    protected $columnWidths;

    public function __construct($collection, array $headings, callable $mappingCallback, array $columnWidths = [])
    {
        $this->collection = $collection;
        $this->headings = $headings;
        $this->mappingCallback = $mappingCallback;
        $this->columnWidths = $columnWidths;
    }

    /**
     * Retornar la colección de datos
     */
    public function collection()
    {
        return $this->collection;
    }

    /**
     * Definir los encabezados
     */
    public function headings(): array
    {
        return $this->headings;
    }

    /**
     * Mapear cada fila usando el callback proporcionado
     */
    public function map($row): array
    {
        return call_user_func($this->mappingCallback, $row);
    }

    /**
     * Definir anchos de columnas
     */
    public function columnWidths(): array
    {
        return $this->columnWidths;
    }

    /**
     * Aplicar estilos a la hoja
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Estilo para la fila de encabezados
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '312AFF'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ],
        ];
    }
}
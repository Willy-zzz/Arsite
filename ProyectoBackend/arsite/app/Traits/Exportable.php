<?php
//app\Traits\Exportable.php
namespace App\Traits;

use App\Exports\GenericExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

trait Exportable
{
    protected function exportData(
        Request $request,
        $collection,
        array $headings,
        callable $mappingCallback,
        string $filename,
        string $title,
        array $columnWidths = [],
        array $pdfOptions = []
    ) {
        $format = $request->get('export', 'excel');
        
        if ($format === 'pdf') {
            return $this->exportToPDF(
                $collection, 
                $headings, 
                $mappingCallback, 
                $filename, 
                $title,
                $pdfOptions
            );
        }
        
        return $this->exportToExcel($collection, $headings, $mappingCallback, $filename, $columnWidths);
    }

    private function exportToExcel($collection, array $headings, callable $mappingCallback, string $filename, array $columnWidths)
    {
        $export = new GenericExport($collection, $headings, $mappingCallback, $columnWidths);
        
        return Excel::download(
            $export,
            $filename . '_' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    private function exportToPDF(
        $collection, 
        array $headings, 
        callable $mappingCallback, 
        string $filename, 
        string $title,
        array $options = []
    ) {
        $data = $collection->map($mappingCallback);
        
        $view = $options['view'] ?? 'exports.generic-pdf';
        
        $viewData = [
            'title' => $title,
            'headers' => $headings,
            'data' => $data,
        ];

        // Agregar datos adicionales si existen
        if (isset($options['summary'])) {
            $viewData['summary'] = $options['summary'];
        }
        
        if (isset($options['filters'])) {
            $viewData['filters'] = $options['filters'];
        }
        
        if (isset($options['notes'])) {
            $viewData['notes'] = $options['notes'];
        }
        
        if (isset($options['generatedBy'])) {
            $viewData['generatedBy'] = $options['generatedBy'];
        }

        
        $pdf = Pdf::loadView($view, $viewData);
        
        $orientation = $options['orientation'] ?? 'landscape';
        $paperSize = $options['paperSize'] ?? 'a4';
        
        $pdf->setPaper($paperSize, $orientation);
        
        return $pdf->download($filename . '_' . now()->format('Y-m-d') . '.pdf');
    }
}
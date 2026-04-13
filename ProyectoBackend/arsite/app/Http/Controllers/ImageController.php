<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImageController extends Controller
{
    /**
     * Servir imágenes desde storage/app/public
     */
    public function show($path)
    {
        // Validar que la ruta no sea maliciosa (directory traversal)
        $path = str_replace(['..', '\\', '/'], '', $path);
        
        $fullPath = storage_path("app/public/{$path}");
        
        if (!file_exists($fullPath)) {
            abort(404);
        }
        
        // Devolver la imagen con el tipo MIME correcto
        $mime = mime_content_type($fullPath);
        return response()->file($fullPath, ['Content-Type' => $mime]);
    }
}
<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class FileUploadService
{
    /**
     * Upload a file to a specific directory in the public folder.
     *
     * @param UploadedFile $file The uploaded file instance
     * @param string $directory The directory name inside public/uploads (e.g., 'kerusakan')
     * @param string $prefix Optional prefix for the filename
     * @return string The relative path to the uploaded file
     */
    public function uploadImage(UploadedFile $file, string $directory, string $prefix = 'img'): string
    {
        $safeDir = preg_replace('/[^a-zA-Z0-9_\-]/', '', $directory) ?: 'uploads';
        $uploadDir = public_path("uploads/{$safeDir}");
        
        if (!file_exists($uploadDir)) {
            @mkdir($uploadDir, 0775, true);
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $extension = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension());
        if (!in_array($extension, $allowedExtensions, true)) {
            $extension = 'jpg';
        }

        $safePrefix = preg_replace('/[^a-zA-Z0-9_\-]/', '', $prefix) ?: 'img';
        $filename = $safePrefix . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $file->move($uploadDir, $filename);

        return "/uploads/{$safeDir}/{$filename}";
    }
}

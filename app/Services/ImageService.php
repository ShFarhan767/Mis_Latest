<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

class ImageService
{
    public static function upload(?UploadedFile $file, string $folder, ?string $oldImage = null): ?string
    {
        if (!$file) {
            return $oldImage; // Return old image if no new file uploaded
        }

        $uploadPath = public_path("uploads/{$folder}/");

        // Create folder if not exists
        if (!File::exists($uploadPath)) {
            File::makeDirectory($uploadPath, 0777, true, true);
        }

        // Generate unique filename
        $filename = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();

        // Move file
        $file->move($uploadPath, $filename);

        // Delete old image if present
        if ($oldImage) {
            self::delete($oldImage, $folder);
        }

        // Return relative path for DB
        return "uploads/{$folder}/{$filename}";
    }

    public static function delete(?string $imagePath, string $folder): void
    {
        if (!$imagePath) return;

        $path = public_path("uploads/{$folder}/" . basename($imagePath));

        if (File::exists($path)) {
            File::delete($path);
        }
    }
}

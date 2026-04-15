<?php

namespace App\Concerns;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait StoresPublicUploads
{
    protected function storePublicUpload(?UploadedFile $file, string $directory, ?string $currentPath = null): ?string
    {
        if ($file === null) {
            return $currentPath;
        }

        $newPath = $file->store($directory, 'public');

        if ($currentPath !== null && $currentPath !== $newPath) {
            Storage::disk('public')->delete($currentPath);
        }

        return $newPath;
    }
}

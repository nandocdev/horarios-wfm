<?php

declare(strict_types=1);

namespace App\Modules\FilesystemModule\Actions;

use App\Modules\FilesystemModule\Models\File;
use App\Modules\FilesystemModule\Models\Folder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteFileSystemItemAction
{
    public function execute(File|Folder $item): void
    {
        DB::transaction(function () use ($item) {
            if ($item instanceof File) {
                $this->deleteFile($item);
            } else {
                $this->deleteFolder($item);
            }
        });
    }

    protected function deleteFile(File $file): void
    {
        if (Storage::disk($file->disk)->exists($file->path)) {
            Storage::disk($file->disk)->delete($file->path);
        }
        $file->delete();
    }

    protected function deleteFolder(Folder $folder): void
    {
        // 1. Eliminar archivos internos
        foreach ($folder->files as $file) {
            $this->deleteFile($file);
        }

        // 2. Eliminar subcarpetas recursivamente
        foreach ($folder->children as $subfolder) {
            $this->deleteFolder($subfolder);
        }

        $folder->delete();
    }
}

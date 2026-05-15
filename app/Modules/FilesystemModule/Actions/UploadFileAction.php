<?php

declare(strict_types=1);

namespace App\Modules\FilesystemModule\Actions;

use App\Modules\CoreModule\Models\User;
use App\Modules\FilesystemModule\Models\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UploadFileAction
{
    /**
     * Sube un archivo y verifica la cuota del usuario.
     * 
     * @throws \Exception Si el usuario excede su cuota de espacio.
     */
    public function execute(UploadedFile $uploadedFile, User $user, ?int $folderId = null): File
    {
        return DB::transaction(function () use ($uploadedFile, $user, $folderId) {
            $size = $uploadedFile->getSize();
            
            // Verificar cuota dinámica
            $quotaBytes = app(\App\Modules\FilesystemModule\Actions\GetUserQuotaAction::class)->execute($user);
            $usedBytes = File::where('user_id', $user->id)->sum('size');
            
            if (($usedBytes + $size) > $quotaBytes) {
                $quotaFormatted = round($quotaBytes / (1024 * 1024), 2);
                throw new \Exception("Has excedido tu límite de almacenamiento ({$quotaFormatted}MB).");
            }

            $path = $uploadedFile->store("uploads/{$user->id}", 'local');

            return File::create([
                'user_id' => $user->id,
                'folder_id' => $folderId,
                'name' => $uploadedFile->getClientOriginalName(),
                'original_name' => $uploadedFile->getClientOriginalName(),
                'path' => $path,
                'disk' => 'local',
                'size' => $size,
                'mime_type' => $uploadedFile->getMimeType(),
                'extension' => $uploadedFile->getClientOriginalExtension(),
            ]);
        });
    }
}

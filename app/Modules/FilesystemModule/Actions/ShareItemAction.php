<?php

declare(strict_types=1);

namespace App\Modules\FilesystemModule\Actions;

use App\Modules\FilesystemModule\Models\File;
use App\Modules\FilesystemModule\Models\FileShare;
use App\Modules\FilesystemModule\Models\Folder;
use Illuminate\Support\Facades\DB;

class ShareItemAction
{
    public function execute(File|Folder $item, int $targetUserId, string $accessLevel = 'view'): FileShare
    {
        return DB::transaction(function () use ($item, $targetUserId, $accessLevel) {
            $data = [
                'user_id' => $targetUserId,
                'shared_by_id' => auth()->id(),
                'access_level' => $accessLevel,
            ];

            if ($item instanceof File) {
                $data['file_id'] = $item->id;
            } else {
                $data['folder_id'] = $item->id;
            }

            return FileShare::updateOrCreate(
                [
                    'file_id' => $data['file_id'] ?? null,
                    'folder_id' => $data['folder_id'] ?? null,
                    'user_id' => $targetUserId,
                ],
                $data
            );
        });
    }
}

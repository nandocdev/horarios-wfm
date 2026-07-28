<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Actions;

use App\Modules\ConnectModule\Models\UploadedFile;
use Illuminate\Http\UploadedFile as HttpUploadedFile;

class UploadAgentCallRecordingAction
{
    public function execute(HttpUploadedFile $file, int $agentCallPerformanceId, int $uploadedBy): UploadedFile
    {
        $path = $file->store('recordings', 'public');

        return UploadedFile::create([
            'agent_call_performance_id' => $agentCallPerformanceId,
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'disk' => 'public',
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => $uploadedBy,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\FilesystemModule\Livewire;

use App\Modules\FilesystemModule\Models\File;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class DownloadCenter extends Component
{
    public string $search = '';

    public function render()
    {
        return view('filesystem::livewire.download-center', [
            'files' => $this->getPublicFiles(),
        ]);
    }

    public function getPublicFiles(): Collection
    {
        return File::query()
            ->where('is_public', true)
            ->when($this->search, fn ($q) => $q->where('name', 'ilike', "%{$this->search}%"))
            ->orderBy('name')
            ->get();
    }

    public function download(int $fileId)
    {
        $file = File::findOrFail($fileId);

        if (! $file->is_public) {
            abort(403);
        }

        return Storage::disk($file->disk)->download($file->path, $file->name);
    }
}

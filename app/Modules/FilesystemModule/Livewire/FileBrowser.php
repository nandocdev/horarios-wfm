<?php

declare(strict_types=1);

namespace App\Modules\FilesystemModule\Livewire;

use App\Modules\CoreModule\Models\User;
use App\Modules\FilesystemModule\Actions\DeleteFileSystemItemAction;
use App\Modules\FilesystemModule\Actions\GetUserQuotaAction;
use App\Modules\FilesystemModule\Actions\ShareItemAction;
use App\Modules\FilesystemModule\Actions\UploadFileAction;
use App\Modules\FilesystemModule\Models\File;
use App\Modules\FilesystemModule\Models\FileShare;
use App\Modules\FilesystemModule\Models\Folder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class FileBrowser extends Component
{
    use WithFileUploads;

    public ?int $currentFolderId = null;

    public string $search = '';

    public string $viewMode = 'my_files'; // my_files, shared

    // Upload state
    public $uploads = [];

    public bool $isUploading = false;

    // Folder creation state
    public string $newFolderName = '';

    public bool $showFolderModal = false;

    // Sharing state
    public $itemToShare = null;

    public $shareTargetUserId = null;

    public string $shareAccessLevel = 'view';

    public string $userSearch = '';

    protected $queryString = [
        'currentFolderId' => ['except' => null],
        'search' => ['except' => ''],
        'viewMode' => ['except' => 'my_files'],
    ];

    public function render()
    {
        return view('filesystem::livewire.file-browser', [
            'folders' => $this->getFolders(),
            'files' => $this->getFiles(),
            'breadcrumbs' => $this->getBreadcrumbs(),
            'stats' => $this->getStorageStats(),
            'folderTree' => $this->getFolderTree(),
        ]);
    }

    public function getFolders(): Collection
    {
        $query = Folder::query()->withCount(['children', 'files']);

        if ($this->viewMode === 'shared') {
            return Folder::whereIn('id', FileShare::where('user_id', auth()->id())->whereNotNull('folder_id')->pluck('folder_id'))
                ->withCount(['children', 'files'])
                ->when($this->search, fn ($q) => $q->where('name', 'ilike', "%{$this->search}%"))
                ->get();
        }

        return $query->where('user_id', auth()->id())
            ->where('parent_id', $this->currentFolderId)
            ->when($this->search, fn ($q) => $q->where('name', 'ilike', "%{$this->search}%"))
            ->get();
    }

    public function getFolderTree(): Collection
    {
        $allFolders = Folder::where('user_id', auth()->id())
            ->orderBy('name')
            ->get();

        return $allFolders->whereNull('parent_id');
    }

    public function getFiles(): Collection
    {
        $query = File::query();

        if ($this->viewMode === 'shared') {
            return File::whereIn('id', FileShare::where('user_id', auth()->id())->whereNotNull('file_id')->pluck('file_id'))
                ->when($this->search, fn ($q) => $q->where('name', 'ilike', "%{$this->search}%"))
                ->get();
        }

        return $query->where('user_id', auth()->id())
            ->where('folder_id', $this->currentFolderId)
            ->when($this->search, fn ($q) => $q->where('name', 'ilike', "%{$this->search}%"))
            ->get();
    }

    public function getBreadcrumbs(): array
    {
        if (! $this->currentFolderId) {
            return [];
        }

        $breadcrumbs = [];
        $folder = Folder::find($this->currentFolderId);

        while ($folder) {
            array_unshift($breadcrumbs, [
                'name' => $folder->name,
                'id' => $folder->id,
            ]);
            $folder = $folder->parent;
        }

        return $breadcrumbs;
    }

    protected function getStorageStats(): array
    {
        $used = File::where('user_id', auth()->id())->sum('size');
        $quota = app(GetUserQuotaAction::class)->execute(auth()->user());

        return [
            'used' => $used,
            'quota' => $quota,
            'percentage' => $quota > 0 ? round(($used / $quota) * 100, 2) : 0,
            'used_formatted' => $this->formatSize((float) $used),
            'quota_formatted' => $this->formatSize((float) $quota),
            'is_full' => $used >= $quota,
        ];
    }

    public function navigateTo(?int $folderId): void
    {
        $this->currentFolderId = $folderId;
        $this->search = '';
    }

    public function createFolder(): void
    {
        $this->validate(['newFolderName' => 'required|min:1|max:255']);

        Folder::create([
            'user_id' => auth()->id(),
            'parent_id' => $this->currentFolderId,
            'name' => $this->newFolderName,
        ]);

        $this->newFolderName = '';
        \Flux::modal('create-folder-modal')->close();
        \Flux::toast('Carpeta creada.');
    }

    public function updatedUploads(): void
    {
        $this->validate([
            'uploads.*' => 'required|file|max:102400', // 100MB max por archivo
        ], [
            'uploads.*.max' => 'El archivo no debe pesar más de 100MB.',
            'uploads.*.file' => 'El archivo debe ser un formato válido.',
        ]);

        $action = app(UploadFileAction::class);
        $successCount = 0;

        foreach ($this->uploads as $upload) {
            try {
                $action->execute($upload, auth()->user(), $this->currentFolderId);
                $successCount++;
            } catch (\Exception $e) {
                \Flux::toast(text: "Error en {$upload->getClientOriginalName()}: " . $e->getMessage(), variant: 'danger');
            }
        }

        $this->uploads = [];
        
        if ($successCount > 0) {
            \Flux::toast($successCount . ' archivo(s) subido(s) correctamente.');
        }
    }

    public function download(int $fileId)
    {
        $file = File::findOrFail($fileId);

        // Autorizar (propietario o compartido)
        if ($file->user_id !== auth()->id() && ! FileShare::where('file_id', $file->id)->where('user_id', auth()->id())->exists()) {
            abort(403);
        }

        return Storage::disk($file->disk)->download($file->path, $file->name);
    }

    public function delete(int $id, string $type): void
    {
        $item = $type === 'file' ? File::findOrFail($id) : Folder::findOrFail($id);

        if ($item->user_id !== auth()->id()) {
            \Flux::toast(text: 'No tienes permiso para eliminar este elemento.', variant: 'danger');

            return;
        }

        app(DeleteFileSystemItemAction::class)->execute($item);
        \Flux::toast('Elemento eliminado.');
    }

    public function share(int $id, string $type): void
    {
        $this->itemToShare = [
            'id' => $id,
            'type' => $type,
            'name' => ($type === 'file' ? File::find($id) : Folder::find($id))->name,
        ];

        \Flux::modal('share-modal')->show();
    }

    public function processShare(): void
    {
        $this->validate(['shareTargetUserId' => 'required']);

        $item = $this->itemToShare['type'] === 'file'
            ? File::findOrFail($this->itemToShare['id'])
            : Folder::findOrFail($this->itemToShare['id']);

        app(ShareItemAction::class)->execute($item, (int) $this->shareTargetUserId, $this->shareAccessLevel);

        $this->itemToShare = null;
        $this->shareTargetUserId = null;
        \Flux::modal('share-modal')->close();
        \Flux::toast('Elemento compartido.');
    }

    public function togglePublic(int $fileId): void
    {
        if (! auth()->user()->can('filesystem.public.manage') && ! auth()->user()->hasAnyRole(['admin', 'wfm'])) {
            \Flux::toast(text: 'No tienes permiso para gestionar archivos públicos.', variant: 'danger');

            return;
        }

        $file = File::findOrFail($fileId);

        if ($file->user_id !== auth()->id()) {
            \Flux::toast(text: 'No tienes permiso para modificar este archivo.', variant: 'danger');

            return;
        }

        $file->update(['is_public' => ! $file->is_public]);

        \Flux::toast($file->is_public ? 'Archivo ahora es público.' : 'Archivo ahora es privado.');
    }

    public function getUsersProperty(): Collection
    {
        if (strlen($this->userSearch) < 2) {
            return collect();
        }

        return User::where('name', 'ilike', "%{$this->userSearch}%")
            ->where('id', '!=', auth()->id())
            ->limit(5)
            ->get();
    }

    protected function formatSize($bytes): string
    {
        $bytes = (float) $bytes;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2).' '.$units[$pow];
    }
}

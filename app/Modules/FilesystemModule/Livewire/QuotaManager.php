<?php

declare(strict_types=1);

namespace App\Modules\FilesystemModule\Livewire;

use App\Modules\CoreModule\Models\Role;
use App\Modules\CoreModule\Models\User;
use App\Modules\FilesystemModule\Models\StorageQuota;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithPagination;

class QuotaManager extends Component
{
    use WithPagination;

    public string $search = '';

    public string $type = 'role'; // 'user' or 'role'

    public ?int $targetId = null;

    public int $limitMb = 100;

    public function mount()
    {
        $this->authorize('admin.system');
    }

    public function save()
    {
        $this->authorize('admin.system');
        $this->validate([
            'type' => 'required|in:user,role',
            'targetId' => 'required',
            'limitMb' => 'required|integer|min:1',
        ]);

        StorageQuota::updateOrCreate(
            ['target_type' => $this->type, 'target_id' => $this->targetId],
            ['quota_limit' => StorageQuota::mbToBytes($this->limitMb)]
        );

        if ($this->type === 'user') {
            Cache::forget("user_quota_{$this->targetId}");
        } else {
            // Para roles, podrías iterar usuarios o simplemente limpiar si es poco frecuente
            // Por simplicidad, limpiaremos el caché del usuario actual si cambia un rol suyo
            Cache::forget('user_quota_'.auth()->id());
        }

        $this->reset(['targetId', 'limitMb']);
        \Flux::toast('Cuota actualizada correctamente.');
    }

    public function delete(int $id)
    {
        $this->authorize('admin.system');
        $quota = StorageQuota::findOrFail($id);

        if ($quota->target_type === 'user') {
            Cache::forget("user_quota_{$quota->target_id}");
        }

        $quota->delete();
        \Flux::toast('Límite eliminado.');
    }

    public function getTargetsProperty()
    {
        if ($this->type === 'role') {
            return Role::all();
        }

        return User::where('name', 'ilike', "%{$this->search}%")
            ->limit(10)
            ->get();
    }

    public function render()
    {
        return view('filesystem::livewire.quota-manager', [
            'quotas' => StorageQuota::paginate(10),
        ]);
    }
}

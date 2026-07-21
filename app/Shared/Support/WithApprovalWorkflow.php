<?php

declare(strict_types=1);

namespace App\Shared\Support;

use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

trait WithApprovalWorkflow
{
    use WithPagination;

    abstract protected function approvalModelClass(): string;

    abstract protected function approvalPermission(): string;

    abstract protected function approveItem(object $item): void;

    abstract protected function rejectItem(object $item, string $reason): void;

    public function approve(int $id): void
    {
        $this->authorize($this->approvalPermission());

        $modelClass = $this->approvalModelClass();
        $item = $modelClass::findOrFail($id);

        try {
            $employee = Auth::user()->employee;
            if (! $employee) {
                throw new \RuntimeException('El usuario autenticado debe tener un perfil de empleado asociado.');
            }

            $this->approveItem($item);

            \Flux::toast('Solicitud aprobada correctamente.', variant: 'success');
        } catch (\Throwable $e) {
            \Flux::toast('Error al aprobar: '.$e->getMessage(), variant: 'danger');
        }
    }

    public function reject(int $id, string $reason = ''): void
    {
        $this->authorize($this->approvalPermission());

        $modelClass = $this->approvalModelClass();
        $item = $modelClass::findOrFail($id);

        try {
            $employee = Auth::user()->employee;
            if (! $employee) {
                throw new \RuntimeException('El usuario autenticado debe tener un perfil de empleado asociado.');
            }

            $this->rejectItem($item, $reason ?: 'Rechazado');

            \Flux::toast('Solicitud rechazada.', variant: 'success');
        } catch (\Throwable $e) {
            \Flux::toast('Error al rechazar: '.$e->getMessage(), variant: 'danger');
        }
    }
}

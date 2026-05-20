<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\WfmModule\Actions\ProcessShiftSwapAction;
use App\Modules\WorkflowsModule\Models\ShiftSwapRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class WfmSwapApprovals extends Component
{
    use WithPagination;

    public function approveSwap($requestId, ProcessShiftSwapAction $action)
    {
        try {
            $action->execute((int) $requestId, Auth::user()->employee->id);
            \Flux::toast('Cambio de turno aprobado y aplicado correctamente.', variant: 'success');
        } catch (\Exception $e) {
            \Flux::toast('Error al aprobar: '.$e->getMessage(), variant: 'danger');
        }
    }

    public function rejectSwap($requestId, $reason = 'Rechazado por WFM')
    {
        $request = ShiftSwapRequest::where('id', $requestId)
            ->where('status', 'accepted')
            ->firstOrFail();

        $request->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);

        \Flux::toast('Cambio de turno rechazado.');
    }

    public function render()
    {
        $requests = ShiftSwapRequest::with(['requester', 'recipient', 'requester.team', 'recipient.team'])
            ->where('status', 'accepted')
            ->orderBy('requested_date', 'asc')
            ->paginate(15);

        return view('wfm::livewire.wfm-swap-approvals', [
            'requests' => $requests,
        ])->layout('layouts.app');
    }
}

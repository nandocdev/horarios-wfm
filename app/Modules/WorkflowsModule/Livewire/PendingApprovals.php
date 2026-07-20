<?php

declare(strict_types=1);

namespace App\Modules\WorkflowsModule\Livewire;

use App\Modules\WorkflowsModule\Actions\ApproveWorkflowAction;
use App\Modules\WorkflowsModule\Actions\RejectWorkflowAction;
use App\Modules\WorkflowsModule\Models\WorkflowRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class PendingApprovals extends Component
{
    use WithPagination;

    public ?int $selectedRequestId = null;

    public ?string $rejectReason = null;

    public function approve(int $requestId, ApproveWorkflowAction $action): void
    {
        $workflow = WorkflowRequest::findOrFail($requestId);

        $this->authorize('approve', $workflow);

        $employee = Auth::user()->employee;
        if (! $employee) {
            return;
        }

        $action->execute($workflow, $employee->id);

        \Flux::toast('Solicitud aprobada correctamente.');
    }

    public function selectForReject(int $requestId): void
    {
        $this->selectedRequestId = $requestId;
        $this->rejectReason = '';
    }

    public function reject(RejectWorkflowAction $action): void
    {
        $this->validate(['rejectReason' => 'required|string|min:5']);

        $workflow = WorkflowRequest::findOrFail($this->selectedRequestId);
        $this->authorize('reject', $workflow);

        $employee = Auth::user()->employee;
        if (! $employee) {
            return;
        }

        $action->execute($workflow, $employee->id, $this->rejectReason ?? '');

        $this->reset(['selectedRequestId', 'rejectReason']);
        \Flux::toast('Solicitud rechazada.');
    }

    public function render()
    {
        $employee = Auth::user()->employee;

        $pendingRequests = $employee
            ? WorkflowRequest::with(['requester', 'approvals'])
                ->forApprover($employee->id)
                ->orderBy('created_at', 'desc')
                ->paginate(10)
            : collect();

        return view('workflows::livewire.pending-approvals', [
            'requests' => $pendingRequests,
        ])->layout('layouts.app');
    }
}

<?php

declare(strict_types=1);

namespace App\Src\Workflows\Presentation\Livewire;

use App\Src\Workflows\Application\DTOs\ProcessApprovalDTO;
use App\Src\Workflows\Application\Handlers\ProcessApprovalHandler;
use App\Src\Workflows\Domain\ValueObjects\WorkflowState;
use App\Src\Workflows\Infrastructure\Persistence\EloquentApprovalRequest;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Bandeja de Aprobaciones')]
class ApprovalInbox extends Component
{
    use WithPagination;

    public string $tab = 'pending';
    public int $processingId = 0;
    public string $comment = '';

    public function approve(int $id): void
    {
        $handler = app(ProcessApprovalHandler::class);

        $handler->handle(new ProcessApprovalDTO(
            approvalRequestId: $id,
            approverId: auth()->id(),
            action: 'approved',
            comment: $this->comment,
        ));

        $this->reset(['processingId', 'comment']);
        toast('Solicitud aprobada.');
    }

    public function reject(int $id): void
    {
        if (empty($this->comment)) {
            $this->addError('comment', 'Debes indicar un motivo de rechazo.');
            return;
        }

        $handler = app(ProcessApprovalHandler::class);

        $handler->handle(new ProcessApprovalDTO(
            approvalRequestId: $id,
            approverId: auth()->id(),
            action: 'rejected',
            comment: $this->comment,
        ));

        $this->reset(['processingId', 'comment']);
        toast('Solicitud rechazada.');
    }

    public function render()
    {
        $query = EloquentApprovalRequest::with('signatures');

        $query->when($this->tab === 'pending', fn ($q) => $q->whereIn('state', [
            WorkflowState::PENDING, WorkflowState::L1_APPROVED, WorkflowState::L2_APPROVED,
        ]));

        $query->when($this->tab === 'history', fn ($q) => $q->whereIn('state', [
            WorkflowState::APPROVED, WorkflowState::REJECTED, WorkflowState::CANCELLED,
        ]));

        return view('workflows::livewire.approval-inbox', [
            'requests' => $query->latest()->paginate(15),
        ]);
    }
}

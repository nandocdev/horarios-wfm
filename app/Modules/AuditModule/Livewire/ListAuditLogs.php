<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\Livewire;

use App\Modules\AuditModule\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class ListAuditLogs extends Component
{
    use WithPagination;

    public string $search = '';

    public string $action = '';

    public string $entityType = '';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public int $perPage = 20;

    public bool $showDetailModal = false;

    public ?AuditLog $selectedLog = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'action' => ['except' => ''],
        'entityType' => ['except' => ''],
        'dateFrom' => ['except' => null],
        'dateTo' => ['except' => null],
        'perPage' => ['except' => 20],
    ];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedAction(): void
    {
        $this->resetPage();
    }

    public function updatedEntityType(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function getQuery(): Builder
    {
        return AuditLog::query()
            ->with('user')
            ->filter([
                'search' => $this->search,
                'action' => $this->action,
                'entity_type' => $this->entityType,
                'date_from' => $this->dateFrom,
                'date_to' => $this->dateTo,
            ])
            ->orderByDesc('created_at');
    }

    public function export(string $format = 'csv')
    {
        $params = http_build_query([
            'search' => $this->search,
            'action' => $this->action,
            'entityType' => $this->entityType,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'format' => $format,
        ]);

        return redirect()->to(route('audit.export').'?'.$params);
    }

    public function showDetail(int $logId): void
    {
        $this->selectedLog = AuditLog::with('user')->findOrFail($logId);
        $this->showDetailModal = true;
    }

    public function closeDetail(): void
    {
        $this->showDetailModal = false;
        $this->selectedLog = null;
    }

    public function render()
    {
        $auditLogs = $this->getQuery()->paginate($this->perPage);

        return view('audit::livewire.list-audit-logs', [
            'auditLogs' => $auditLogs,
        ]);
    }
}

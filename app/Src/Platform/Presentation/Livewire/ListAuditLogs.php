<?php

declare(strict_types=1);

namespace App\Src\Platform\Presentation\Livewire;

use App\Src\Platform\Infrastructure\Persistence\EloquentAuditLog;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

final class ListAuditLogs extends Component {
    use WithPagination;

    public string $search = '';

    public string $actionFilter = '';

    public string $entityType = '';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public int $perPage = 20;

    public bool $showDetailModal = false;

    public ?EloquentAuditLog $selectedLog = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'actionFilter' => ['except' => ''],
        'entityType' => ['except' => ''],
        'dateFrom' => ['except' => null],
        'dateTo' => ['except' => null],
        'perPage' => ['except' => 20],
    ];

    public function updatedSearch(): void { $this->resetPage(); }

    public function updatedActionFilter(): void { $this->resetPage(); }

    public function updatedEntityType(): void { $this->resetPage(); }

    public function updatedDateFrom(): void { $this->resetPage(); }

    public function updatedDateTo(): void { $this->resetPage(); }

    public function updatedPerPage(): void { $this->resetPage(); }

    public function showDetail(int $logId): void {
        $this->selectedLog = EloquentAuditLog::with('user')->findOrFail($logId);
        $this->showDetailModal = true;
    }

    public function closeDetail(): void {
        $this->showDetailModal = false;
        $this->selectedLog = null;
    }

    public function export(string $format = 'csv') {
        $params = http_build_query([
            'search' => $this->search,
            'action' => $this->actionFilter,
            'entityType' => $this->entityType,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'format' => $format,
        ]);

        return redirect()->to(route('platform.audit.export', [], false) . '?' . $params);
    }

    public function render() {
        $auditLogs = $this->getQuery()->paginate($this->perPage);

        return view('platform::livewire.list-audit-logs', [
            'auditLogs' => $auditLogs,
        ]);
    }

    private function getQuery(): Builder {
        return EloquentAuditLog::query()
            ->with('user')
            ->filter([
                'search' => $this->search,
                'action' => $this->actionFilter,
                'entity_type' => $this->entityType,
                'date_from' => $this->dateFrom,
                'date_to' => $this->dateTo,
            ])
            ->orderByDesc('created_at');
    }
}

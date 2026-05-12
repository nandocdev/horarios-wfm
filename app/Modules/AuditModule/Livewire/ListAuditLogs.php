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
            ->when($this->search, function (Builder $query) {
                $query->where('entity_type', 'ilike', "%{$this->search}%")
                    ->orWhere('action', 'ilike', "%{$this->search}%")
                    ->orWhere('ip_address', 'ilike', "%{$this->search}%");
            })
            ->when($this->action, fn (Builder $query) => $query->where('action', $this->action))
            ->when($this->entityType, fn (Builder $query) => $query->where('entity_type', $this->entityType))
            ->when($this->dateFrom, fn (Builder $query) => $query->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn (Builder $query) => $query->whereDate('created_at', '<=', $this->dateTo))
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

    public function render()
    {
        $auditLogs = $this->getQuery()->paginate($this->perPage);

        return view('audit::livewire.list-audit-logs', [
            'auditLogs' => $auditLogs,
        ]);
    }
}

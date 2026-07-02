<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\Presentation\Livewire;

use App\Modules\AuditModule\Domain\Repositories\AuditLogRepository;
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

    public ?array $selectedLogData = null;

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
        $repository = app(AuditLogRepository::class);
        $entry = $repository->findById($logId);

        if ($entry !== null) {
            $this->selectedLogData = [
                'id' => $entry->id(),
                'entity_type' => $entry->entityType()->value(),
                'entity_id' => $entry->entityId()->value(),
                'action' => $entry->action()->value(),
                'before' => $entry->before()?->data(),
                'after' => $entry->after()?->data(),
                'user_id' => $entry->userId()?->value(),
                'ip_address' => $entry->ipAddress()?->value(),
                'created_at' => $entry->createdAt()->format('Y-m-d H:i:s'),
            ];
        }

        $this->showDetailModal = true;
    }

    public function closeDetail(): void
    {
        $this->showDetailModal = false;
        $this->selectedLogData = null;
    }

    public function render()
    {
        $repository = app(AuditLogRepository::class);
        $result = $repository->paginate(
            filters: array_filter([
                'search' => $this->search ?: null,
                'action' => $this->action ?: null,
                'entity_type' => $this->entityType ?: null,
                'date_from' => $this->dateFrom,
                'date_to' => $this->dateTo,
            ], fn ($v) => $v !== null && $v !== ''),
            perPage: $this->perPage,
            page: $this->getPage(),
        );

        return view('audit::livewire.list-audit-logs', [
            'auditLogs' => $result['paginator'],
            'entries' => $result['items'],
        ]);
    }
}

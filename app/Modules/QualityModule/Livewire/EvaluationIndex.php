<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Livewire;

use App\Modules\QualityModule\Models\Queue;
use App\Shared\Contracts\Quality\EvaluationRepositoryInterface;
use Livewire\Component;
use Livewire\WithPagination;

class EvaluationIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public ?string $queueFilter = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public ?string $statusFilter = null;

    public string $sortField = 'dteval';

    public string $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'queueFilter' => ['except' => null],
        'dateFrom' => ['except' => null],
        'dateTo' => ['except' => null],
        'statusFilter' => ['except' => null],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingQueueFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function render(EvaluationRepositoryInterface $repo)
    {
        $evaluations = $repo->paginated(
            filters: array_filter([
                'fecha_desde' => $this->dateFrom,
                'fecha_hasta' => $this->dateTo,
                'queue_id' => $this->queueFilter,
                'status' => $this->statusFilter,
            ]),
            sortField: $this->sortField,
            sortDirection: $this->sortDirection,
        );

        return view('quality::livewire.evaluation-index', [
            'evaluations' => $evaluations,
            'queues' => Queue::orderBy('name')->get(),
        ]);
    }
}

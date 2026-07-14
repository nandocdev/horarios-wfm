<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Livewire;

use App\Shared\Contracts\Quality\CriteriaRepositoryInterface;
use Livewire\Component;

class CriteriaList extends Component
{
    public function render(CriteriaRepositoryInterface $repo)
    {
        return view('quality::livewire.criteria-list', [
            'criterias' => $repo->all(),
        ]);
    }
}

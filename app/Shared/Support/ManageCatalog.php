<?php

declare(strict_types=1);

namespace App\Shared\Support;

use Livewire\WithPagination;

trait ManageCatalog
{
    use WithPagination;

    public bool $showModal = false;

    public string $search = '';

    abstract protected function catalogModel(): string;

    abstract protected function catalogLabel(): string;

    public function create(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $modelClass = $this->catalogModel();
        $record = $modelClass::findOrFail($id);
        $this->loadForm($record);
        $this->showModal = true;
    }

    public function delete(int $id): void
    {
        $modelClass = $this->catalogModel();
        $record = $modelClass::findOrFail($id);
        $this->authorizeIfNeeded($record);
        $record->delete();
        \Flux::toast($this->catalogLabel().' eliminado.');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    protected function authorizeIfNeeded(object $record): void
    {
        // Override in component to add authorization
    }

    abstract protected function resetForm(): void;

    abstract protected function loadForm(object $record): void;
}

<?php

declare(strict_types=1);

namespace App\Modules\KnowledgeModule\Livewire;

use App\Modules\DirectoryModule\Models\Unit;
use App\Modules\KnowledgeModule\Actions\CreateArticleAction;
use App\Modules\KnowledgeModule\Actions\UpdateArticleAction;
use App\Modules\KnowledgeModule\Livewire\Forms\KnowledgeArticleForm;
use App\Modules\KnowledgeModule\Models\KnowledgeArticle;
use App\Modules\KnowledgeModule\Models\KnowledgeCategory;
use App\Modules\KnowledgeModule\Models\Queue;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Componente Livewire dedicado a la creación y edición de artículos.
 * Reemplaza la funcionalidad anterior de modal por una vista independiente.
 */
class UpsertKnowledgeArticle extends Component
{
    use AuthorizesRequests;

    public KnowledgeArticleForm $form;

    public ?KnowledgeArticle $article = null;

    public string $activeTab = 'write';

    /**
     * Inicializa el componente. Si se provee ID, se carga para edición.
     */
    public function mount(?int $id = null): void
    {
        if ($id) {
            $this->article = KnowledgeArticle::findOrFail($id);
            $this->authorize('update', $this->article);
            $this->form->setArticle($this->article);
        } else {
            $this->authorize('create', KnowledgeArticle::class);
            $this->form->resetForm();
        }
    }

    /**
     * Guarda el artículo y redirecciona al panel administrativo.
     */
    public function save(CreateArticleAction $createAction, UpdateArticleAction $updateAction)
    {
        $this->form->validate();

        $dto = $this->form->toDTO();
        $userId = (int) Auth::id();

        if ($this->article) {
            $this->authorize('update', $this->article);
            $updateAction->execute($this->article, $dto, $userId);
            \Flux::toast('Artículo actualizado correctamente.');
        } else {
            $this->authorize('create', KnowledgeArticle::class);
            $createAction->execute($dto, $userId);
            \Flux::toast('Artículo creado correctamente.');
        }

        return $this->redirectRoute('knowledge.admin', navigate: true);
    }

    /**
     * Renderiza la vista de creación/edición.
     */
    public function render()
    {
        $categories = KnowledgeCategory::orderBy('name')->get();
        $queues = Queue::where('is_active', true)->orderBy('name')->get();
        $units = Unit::with('building')->where('is_active', true)->orderBy('id')->get();

        return view('knowledge::livewire.upsert-knowledge-article', [
            'categories' => $categories,
            'queues' => $queues,
            'units' => $units,
        ])->layout('layouts.app');
    }
}

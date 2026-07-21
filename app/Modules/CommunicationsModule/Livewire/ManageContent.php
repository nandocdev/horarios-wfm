<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Livewire;

use App\Modules\CommunicationsModule\Models\News;
use App\Modules\CommunicationsModule\Models\Poll;
use App\Modules\CommunicationsModule\Models\Shoutout;
use Livewire\Component;
use Livewire\WithPagination;

class ManageContent extends Component
{
    use WithPagination;

    public string $search = '';

    public string $contentType = '';

    protected function typeConfig(): array
    {
        return [
            'news' => [
                'model' => News::class,
                'title' => 'Panel de Noticias',
                'description' => 'Gestiona el contenido informativo de la plataforma.',
                'create_route' => 'communications.news.create',
                'create_label' => 'Nueva Noticia',
                'with' => ['author'],
                'search_fields' => ['title', 'excerpt'],
                'order_by' => 'published_at',
                'order_dir' => 'desc',
                'deleted_message' => 'Noticia eliminada correctamente.',
            ],
            'polls' => [
                'model' => Poll::class,
                'title' => 'Panel de Encuestas',
                'description' => 'Gestiona las encuestas operativas para el personal.',
                'create_route' => 'communications.polls.create',
                'create_label' => 'Nueva Encuesta',
                'with' => [],
                'with_counts' => ['responses'],
                'search_fields' => ['question'],
                'order_by' => 'created_at',
                'order_dir' => 'desc',
                'deleted_message' => 'Encuesta eliminada correctamente.',
            ],
            'shoutouts' => [
                'model' => Shoutout::class,
                'title' => 'Panel de Reconocimientos',
                'description' => 'Gestiona los reconocimientos públicos entre colaboradores.',
                'create_route' => 'communications.shoutouts.create',
                'create_label' => 'Nuevo Reconocimiento',
                'with' => ['employee'],
                'search_fields' => ['message'],
                'order_by' => 'created_at',
                'order_dir' => 'desc',
                'deleted_message' => 'Reconocimiento eliminado correctamente.',
            ],
        ];
    }

    public function mount(): void
    {
        $this->contentType = $this->resolveTypeFromUrl();
        $this->authorize($this->contentType === 'news' ? 'create' : 'manage', $this->resolveModelClass());
    }

    protected function resolveTypeFromUrl(): string
    {
        $segment = request()->segment(3);

        return match ($segment) {
            'news' => 'news',
            'polls' => 'polls',
            'shoutouts' => 'shoutouts',
            default => abort(404, 'Tipo de contenido no válido.'),
        };
    }

    protected function resolveModelClass(): string
    {
        return $this->typeConfig()[$this->contentType]['model'];
    }

    public function delete(int $id): void
    {
        $modelClass = $this->resolveModelClass();
        $item = $modelClass::findOrFail($id);
        $this->authorize('delete', $item);

        $item->delete();
        toast($this->typeConfig()[$this->contentType]['deleted_message']);
    }

    public function archive(int $id): void
    {
        $poll = Poll::findOrFail($id);
        $this->authorize('archive', $poll);

        $poll->archive();
        toast('Encuesta cerrada correctamente.');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $config = $this->typeConfig()[$this->contentType];
        $modelClass = $config['model'];

        $query = $modelClass::with($config['with']);

        if (! empty($config['with_counts'])) {
            foreach ($config['with_counts'] as $relation) {
                $query->withCount($relation);
            }
        }

        $query->where(function ($q) use ($config) {
            foreach ($config['search_fields'] as $i => $field) {
                if ($i === 0) {
                    $q->where($field, 'ilike', "%{$this->search}%");
                } else {
                    $q->orWhere($field, 'ilike', "%{$this->search}%");
                }
            }
        });

        $items = $query->orderBy($config['order_by'], $config['order_dir'])->paginate(10);

        return view('communications::livewire.manage-content', [
            'items' => $items,
            'config' => $config,
        ]);
    }
}

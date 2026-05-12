<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Livewire;

use App\Modules\CommunicationsModule\Actions\UpdateNewsAction;
use App\Modules\CommunicationsModule\Livewire\Forms\NewsForm;
use App\Modules\CommunicationsModule\Models\Category;
use App\Modules\CommunicationsModule\Models\News;
use App\Modules\CommunicationsModule\Models\Tag;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Vista de edición de noticias en el panel de administración.
 */
class EditNews extends Component
{
    use WithFileUploads;

    public NewsForm $form;

    public News $news;

    /**
     * Inicialización con el modelo de noticia cargado.
     */
    public function mount(News $news): void
    {
        $this->authorize('update', $news);
        $this->news = $news;
        $this->form->setNews($news);
    }

    /**
     * Actualiza la noticia mediante la acción de negocio.
     */
    public function update(UpdateNewsAction $action)
    {
        $this->form->validate();

        $action->execute($this->news, $this->form->toDTO());

        toast('Noticia actualizada satisfactoriamente.');

        $this->redirectRoute('communications.news.index', navigate: true);
    }

    /**
     * Elimina un archivo adjunto específico.
     */
    public function deleteMedia(int $mediaId): void
    {
        $media = $this->news->media()->findOrFail($mediaId);
        $media->delete();
        toast('Archivo eliminado correctamente.');
    }

    /**
     * Renderizado del formulario.
     */
    public function render()
    {
        return view('communications::livewire.news-form', [
            'mode' => 'edit',
            'categories' => Category::query()->active()->ordered()->get(['id', 'name']),
            'tags' => Tag::query()->active()->ordered()->get(['id', 'name']),
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CommunicationsModule\Actions\CreateTagAction;
use App\Modules\CommunicationsModule\Actions\DeleteTagAction;
use App\Modules\CommunicationsModule\Actions\UpdateTagAction;
use App\Modules\CommunicationsModule\DTOs\TagDTO;
use App\Modules\CommunicationsModule\Http\Requests\StoreTagRequest;
use App\Modules\CommunicationsModule\Http\Requests\UpdateTagRequest;
use App\Modules\CommunicationsModule\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Controlador para gestión de tags.
 */
class TagController extends Controller
{
    /**
     * Lista todos los tags.
     */
    public function index(): View
    {
        $tags = Tag::active()->ordered()->get();

        return view('communications::admin.tags.index', compact('tags'));
    }

    /**
     * Muestra el formulario para crear un tag.
     */
    public function create(): View
    {
        return view('communications::admin.tags.create');
    }

    /**
     * Almacena un nuevo tag.
     */
    public function store(
        StoreTagRequest $request,
        CreateTagAction $action
    ): RedirectResponse {
        $dto = TagDTO::fromArray($request->validated());
        $tag = $action->execute($dto);

        return redirect()
            ->route('communications.admin.tags.show', $tag)
            ->with('success', 'Tag creado correctamente.');
    }

    /**
     * Muestra un tag específico.
     */
    public function show(Tag $tag): View
    {
        $tag->load(['news', 'polls', 'shoutouts']);

        return view('communications::admin.tags.show', compact('tag'));
    }

    /**
     * Muestra el formulario para editar un tag.
     */
    public function edit(Tag $tag): View
    {
        return view('communications::admin.tags.edit', compact('tag'));
    }

    /**
     * Actualiza un tag existente.
     */
    public function update(
        UpdateTagRequest $request,
        Tag $tag,
        UpdateTagAction $action
    ): RedirectResponse {
        $dto = TagDTO::fromArray($request->validated());
        $tag = $action->execute($tag, $dto);

        return redirect()
            ->route('communications.admin.tags.show', $tag)
            ->with('success', 'Tag actualizado correctamente.');
    }

    /**
     * Elimina un tag.
     */
    public function destroy(
        Tag $tag,
        DeleteTagAction $action
    ): RedirectResponse {
        $action->execute($tag);

        return redirect()
            ->route('communications.admin.tags.index')
            ->with('success', 'Tag eliminado correctamente.');
    }
}

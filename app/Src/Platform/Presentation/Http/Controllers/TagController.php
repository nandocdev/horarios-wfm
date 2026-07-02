<?php

declare(strict_types=1);

namespace App\Src\Platform\Presentation\Http\Controllers;

use App\Src\Platform\Application\DTOs\TagDTO;
use App\Src\Platform\Application\Handlers\CreateTagHandler;
use App\Src\Platform\Application\Handlers\UpdateTagHandler;
use App\Src\Platform\Application\Handlers\DeleteTagHandler;
use App\Src\Platform\Presentation\Policies\TagPolicy;
use App\Modules\CommunicationsModule\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class TagController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', TagPolicy::class);

        $tags = Tag::active()->ordered()->get();

        return view('platform::livewire.admin.tags.index', compact('tags'));
    }

    public function create(): View
    {
        $this->authorize('create', TagPolicy::class);

        return view('platform::livewire.admin.tags.form', ['tag' => null, 'mode' => 'create']);
    }

    public function store(Request $request, CreateTagHandler $handler): RedirectResponse
    {
        $this->authorize('create', TagPolicy::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:7'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $dto = TagDTO::fromArray($validated);
        $handler->execute($dto);

        return redirect()->route('platform.communications.admin.tags.index')
            ->with('success', 'Tag creado correctamente.');
    }

    public function show(Tag $tag): View
    {
        $this->authorize('view', $tag);

        return view('platform::livewire.admin.tags.form', ['tag' => $tag, 'mode' => 'show']);
    }

    public function edit(Tag $tag): View
    {
        $this->authorize('update', $tag);

        return view('platform::livewire.admin.tags.form', ['tag' => $tag, 'mode' => 'edit']);
    }

    public function update(Request $request, Tag $tag, UpdateTagHandler $handler): RedirectResponse
    {
        $this->authorize('update', $tag);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:7'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $dto = TagDTO::fromArray($validated);
        $handler->execute($tag->id, $dto);

        return redirect()->route('platform.communications.admin.tags.index')
            ->with('success', 'Tag actualizado correctamente.');
    }

    public function destroy(Tag $tag, DeleteTagHandler $handler): RedirectResponse
    {
        $this->authorize('delete', $tag);

        $handler->execute($tag->id);

        return redirect()->route('platform.communications.admin.tags.index')
            ->with('success', 'Tag eliminado correctamente.');
    }
}

<?php

declare(strict_types=1);

namespace App\Src\Platform\Presentation\Http\Controllers;

use App\Src\Platform\Application\DTOs\CategoryDTO;
use App\Src\Platform\Application\Handlers\CreateCategoryHandler;
use App\Src\Platform\Application\Handlers\UpdateCategoryHandler;
use App\Src\Platform\Application\Handlers\DeleteCategoryHandler;
use App\Src\Platform\Presentation\Policies\CategoryPolicy;
use App\Modules\CommunicationsModule\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class CategoryController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', CategoryPolicy::class);

        $categories = Category::active()->ordered()->get();

        return view('platform::livewire.admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        $this->authorize('create', CategoryPolicy::class);

        return view('platform::livewire.admin.categories.form', ['category' => null, 'mode' => 'create']);
    }

    public function store(Request $request, CreateCategoryHandler $handler): RedirectResponse
    {
        $this->authorize('create', CategoryPolicy::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'color' => ['nullable', 'string', 'max:7'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $dto = CategoryDTO::fromArray($validated);
        $category = $handler->execute($dto);

        return redirect()->route('platform.communications.admin.categories.index')
            ->with('success', 'Categoría creada correctamente.');
    }

    public function show(Category $category): View
    {
        $this->authorize('view', $category);

        return view('platform::livewire.admin.categories.form', ['category' => $category, 'mode' => 'show']);
    }

    public function edit(Category $category): View
    {
        $this->authorize('update', $category);

        return view('platform::livewire.admin.categories.form', ['category' => $category, 'mode' => 'edit']);
    }

    public function update(Request $request, Category $category, UpdateCategoryHandler $handler): RedirectResponse
    {
        $this->authorize('update', $category);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'color' => ['nullable', 'string', 'max:7'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $dto = CategoryDTO::fromArray($validated);
        $handler->execute($category->id, $dto);

        return redirect()->route('platform.communications.admin.categories.index')
            ->with('success', 'Categoría actualizada correctamente.');
    }

    public function destroy(Category $category, DeleteCategoryHandler $handler): RedirectResponse
    {
        $this->authorize('delete', $category);

        $handler->execute($category->id);

        return redirect()->route('platform.communications.admin.categories.index')
            ->with('success', 'Categoría eliminada correctamente.');
    }
}

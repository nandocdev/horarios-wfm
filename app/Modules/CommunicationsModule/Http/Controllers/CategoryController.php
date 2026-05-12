<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CommunicationsModule\Actions\CreateCategoryAction;
use App\Modules\CommunicationsModule\Actions\DeleteCategoryAction;
use App\Modules\CommunicationsModule\Actions\UpdateCategoryAction;
use App\Modules\CommunicationsModule\DTOs\CategoryDTO;
use App\Modules\CommunicationsModule\Http\Requests\StoreCategoryRequest;
use App\Modules\CommunicationsModule\Http\Requests\UpdateCategoryRequest;
use App\Modules\CommunicationsModule\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Controlador para gestión de categorías.
 */
class CategoryController extends Controller
{
    /**
     * Lista todas las categorías.
     */
    public function index(): View
    {
        $categories = Category::active()->ordered()->get();

        return view('communications::admin.categories.index', compact('categories'));
    }

    /**
     * Muestra el formulario para crear una categoría.
     */
    public function create(): View
    {
        return view('communications::admin.categories.create');
    }

    /**
     * Almacena una nueva categoría.
     */
    public function store(
        StoreCategoryRequest $request,
        CreateCategoryAction $action
    ): RedirectResponse {
        $dto = CategoryDTO::fromArray($request->validated());
        $category = $action->execute($dto);

        return redirect()
            ->route('communications.admin.categories.show', $category)
            ->with('success', 'Categoría creada correctamente.');
    }

    /**
     * Muestra una categoría específica.
     */
    public function show(Category $category): View
    {
        return view('communications::admin.categories.show', compact('category'));
    }

    /**
     * Muestra el formulario para editar una categoría.
     */
    public function edit(Category $category): View
    {
        return view('communications::admin.categories.edit', compact('category'));
    }

    /**
     * Actualiza una categoría existente.
     */
    public function update(
        UpdateCategoryRequest $request,
        Category $category,
        UpdateCategoryAction $action
    ): RedirectResponse {
        $dto = CategoryDTO::fromArray($request->validated());
        $updatedCategory = $action->execute($category, $dto);

        return redirect()
            ->route('communications.admin.categories.show', $updatedCategory)
            ->with('success', 'Categoría actualizada correctamente.');
    }

    /**
     * Elimina una categoría.
     */
    public function destroy(
        Category $category,
        DeleteCategoryAction $action
    ): RedirectResponse {
        $action->execute($category);

        return redirect()
            ->route('communications.admin.categories.index')
            ->with('success', 'Categoría eliminada correctamente.');
    }
}

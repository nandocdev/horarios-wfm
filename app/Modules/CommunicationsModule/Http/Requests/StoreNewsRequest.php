<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación para crear una noticia.
 */
class StoreNewsRequest extends FormRequest
{
    /**
     * Autorización basada en permisos.
     */
    public function authorize(): bool
    {
        return $this->user()->can('news.create');
    }

    /**
     * Reglas de validación.
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:news,slug'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'published_at' => ['nullable', 'date', 'after:now'],
            'is_active' => ['boolean'],
            'featured_image' => ['nullable', 'image', 'max:2048'],
            'attachments.*' => ['nullable', 'file', 'max:10240'],
        ];
    }

    /**
     * Mensajes de error personalizados.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'El título es obligatorio.',
            'slug.unique' => 'Este slug ya está en uso.',
            'published_at.after' => 'La fecha de publicación debe ser futura.',
            'featured_image.image' => 'La imagen destacada debe ser un archivo de imagen.',
            'featured_image.max' => 'La imagen destacada no puede superar los 2MB.',
            'attachments.*.max' => 'Los archivos adjuntos no pueden superar los 10MB.',
        ];
    }
}

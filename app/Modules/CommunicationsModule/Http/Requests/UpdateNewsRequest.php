<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación para actualizar una noticia.
 */
class UpdateNewsRequest extends FormRequest
{
    /**
     * Autorización basada en permisos.
     */
    public function authorize(): bool
    {
        return $this->user()->can('news.edit');
    }

    /**
     * Reglas de validación.
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('news', 'slug')->ignore($this->route('news')),
            ],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'published_at' => ['nullable', 'date'],
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
            'featured_image.image' => 'La imagen destacada debe ser un archivo de imagen.',
            'featured_image.max' => 'La imagen destacada no puede superar los 2MB.',
            'attachments.*.max' => 'Los archivos adjuntos no pueden superar los 10MB.',
        ];
    }
}

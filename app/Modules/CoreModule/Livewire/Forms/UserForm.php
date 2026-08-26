<?php

declare(strict_types=1);

namespace App\Modules\CoreModule\Livewire\Forms;

use App\Modules\CoreModule\DTOs\UserDTO;
use App\Modules\CoreModule\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Form;

/**
 * Form Object para la gestión de datos de usuario en Livewire.
 */
class UserForm extends Form
{
    public ?User $user = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public bool $is_active = true;

    public bool $force_password_change = false;

    public array $roles = [];

    /**
     * Define las reglas de validación dinámicas.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user?->id),
            ],
            'password' => [$this->user ? 'nullable' : 'required', 'min:5'],
            'is_active' => ['boolean'],
            'force_password_change' => ['boolean'],
            'roles' => ['array'],
        ];
    }

    /**
     * Llena el formulario con los datos de un usuario existente.
     */
    public function set(User $user): void
    {
        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->is_active = (bool) $user->is_active;
        $this->force_password_change = (bool) $user->force_password_change;
        $this->roles = $user->roles->pluck('name')->toArray();
    }

    /**
     * Mapea el estado del formulario hacia un DTO inmutable.
     */
    public function toDTO(): UserDTO
    {
        return new UserDTO(
            name: $this->name,
            email: $this->email,
            password: $this->password ?: null,
            is_active: $this->is_active,
            force_password_change: $this->force_password_change,
            roles: $this->roles
        );
    }
}

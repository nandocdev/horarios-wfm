<?php

declare(strict_types=1);

namespace App\Modules\CoreModule\Actions\Fortify;

use App\Modules\CoreModule\Concerns\PasswordValidationRules;
use App\Modules\CoreModule\Concerns\ProfileValidationRules;
use App\Modules\CoreModule\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

/**
 * Crea un usuario institucional con rol por defecto y validación segura.
 *
 * [UC-INT-01] Creación de usuario con rol mínimo 'agent' o 'admin'
 * [RIESGO] Contraseña débil — mitigado por PasswordValidationRules institucional
 * [RIESGO] Usuario sin rol — mitigado por asignación automática de rol por defecto
 * [RIESGO] Email duplicado — mitigado por Rule::unique en profileRules()
 */
class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Rol por defecto asignado a todos los nuevos usuarios institucionales.
     * Evita que un quede sin rol y sin permisos de acceso.
     */
    private const DEFAULT_ROLE = 'agent';

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'is_active' => true,
            'force_password_change' => true,
        ]);

        // [UC-INT-01] Asignar rol por defecto para garantizar acceso al sistema
        if (! $user->hasRole(self::DEFAULT_ROLE)) {
            $user->assignRole(self::DEFAULT_ROLE);
        }

        return $user;
    }

    /**
     * Get the profile validation rules for name and email.
     *
     * @return array<int, Rule|array<mixed>|string>
     */
    protected function profileRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                \Illuminate\Validation\Rule::unique(User::class),
            ],
        ];
    }
}

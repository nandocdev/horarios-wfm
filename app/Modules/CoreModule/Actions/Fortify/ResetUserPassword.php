<?php

declare(strict_types=1);

namespace App\Modules\CoreModule\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Modules\CoreModule\Models\User;
use App\Modules\CoreModule\Notifications\PasswordChangedNotification;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    /**
     * Validate and reset the user's forgotten password.
     *
     * @param  array<string, string>  $input
     */
    public function reset(User $user, array $input): void
    {
        Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        $user->forceFill([
            'password' => $input['password'],
            'force_password_change' => false,
        ])->save();

        $user->notify(new PasswordChangedNotification());
    }
}

<?php

declare(strict_types=1);

namespace App\Src\Identity\Presentation\Http;

use App\Modules\CoreModule\Models\User;
use App\Src\Identity\Application\DTOs\LoginDTO;
use App\Src\Identity\Application\Handlers\AuthenticateUserHandler;
use App\Src\Shared\Domain\ValueObjects\Email;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

final class AuthController
{
    public function __construct(
        private AuthenticateUserHandler $authenticateHandler,
    ) {}

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $dto = new LoginDTO(
            email: new Email($request->string('email')->toString()),
            password: $request->string('password')->toString(),
        );

        $result = $this->authenticateHandler->handle($dto);

        if (! $result->isSuccess()) {
            throw ValidationException::withMessages([
                'email' => $result->error() ?? __('These credentials do not match our records.'),
            ]);
        }

        $domainUser = $result->user();
        $legacyUser = User::find($domainUser->id());

        if ($legacyUser === null) {
            throw ValidationException::withMessages([
                'email' => __('These credentials do not match our records.'),
            ]);
        }

        Auth::login($legacyUser, $request->boolean('remember'));

        $request->session()->regenerate();

        return redirect()->intended(config('fortify.home', '/dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}

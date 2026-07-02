<?php

declare(strict_types=1);

namespace App\Src\Identity\Presentation\Http\Controllers;

use App\Modules\CoreModule\Models\User as LegacyUser;
use App\Src\Identity\Application\DTOs\LoginDTO;
use App\Src\Identity\Application\Handlers\AuthenticateUserHandler;
use App\Src\Shared\Domain\ValueObjects\Email;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Laravel\Fortify\Fortify;

final class AuthController
{
    public function __construct(
        private AuthenticateUserHandler $authenticateHandler,
    ) {}

    public function create(): View
    {
        return view('identity::auth.login');
    }

    public function store(Request $request): RedirectResponse
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
        $legacyUser = LegacyUser::find($domainUser->id());

        if ($legacyUser === null) {
            throw ValidationException::withMessages([
                'email' => __('These credentials do not match our records.'),
            ]);
        }

        Auth::login($legacyUser, $request->boolean('remember'));

        $request->session()->regenerate();

        return redirect()->intended(config('fortify.home', '/dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function forgotPassword(): View
    {
        return view('identity::auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', __($status));
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }

    public function resetPassword(string $token): View
    {
        return view('identity::auth.reset-password', ['token' => $token]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'force_password_change' => false,
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('identity.login')->with('status', __($status));
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }

    public function verifyEmail(): View
    {
        return view('identity::auth.verify-email');
    }

    public function twoFactorChallenge(): View
    {
        return view('identity::auth.two-factor-challenge');
    }

    public function verifyTwoFactor(Request $request): RedirectResponse
    {
        $confirmed = Auth::guard('fortify')->attemptTwoFactorAuthentication(
            $request->input('code'),
            $request->boolean('remember'),
        );

        if (! $confirmed) {
            throw ValidationException::withMessages([
                'code' => __('The provided two factor authentication code was invalid.'),
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(config('fortify.home', '/dashboard'));
    }

    public function confirmPassword(): View
    {
        return view('identity::auth.confirm-password');
    }

    public function verifyPassword(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'string']]);

        if (! Auth::guard('web')->validate([
            'email' => $request->user()->email,
            'password' => $request->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('The password is incorrect.'),
            ]);
        }

        $request->session()->put('auth.password_confirmed_at', time());

        return redirect()->intended();
    }
}

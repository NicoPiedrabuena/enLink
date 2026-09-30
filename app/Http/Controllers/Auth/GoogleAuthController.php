<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(): SymfonyRedirectResponse|RedirectResponse
    {
        if (! config('services.google.client_id') || ! config('services.google.client_secret')) {
            return Redirect::route('login')->withErrors(['email' => 'El acceso con Google todavía no está configurado.']);
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            $email = Str::lower((string) $googleUser->getEmail());

            if ($email === '' || ! $googleUser->getId()) {
                return Redirect::route('login')->withErrors(['email' => 'Google no compartió un correo válido.']);
            }

            $user = User::query()
                ->where('google_id', $googleUser->getId())
                ->orWhere('email', $email)
                ->first();

            if ($user?->status === 'suspended') {
                return Redirect::route('login')->withErrors(['email' => 'Esta cuenta se encuentra suspendida.']);
            }

            if ($user) {
                $user->forceFill([
                    'google_id' => $googleUser->getId(),
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ])->save();
            } else {
                $user = User::forceCreate([
                    'name' => $googleUser->getName() ?: Str::before($email, '@'),
                    'email' => $email,
                    'google_id' => $googleUser->getId(),
                    'email_verified_at' => now(),
                    'password' => Str::password(40),
                ]);
            }

            Auth::login($user, true);
            request()->session()->regenerate();

            return Redirect::intended(route('dashboard', absolute: false));
        } catch (Throwable $exception) {
            Log::warning('Google OAuth failed.', ['exception' => $exception::class]);

            return Redirect::route('login')->withErrors(['email' => 'No pudimos completar el acceso con Google. Intentá nuevamente.']);
        }
    }
}

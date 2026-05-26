<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    // ─── Google ──────────────────────────────────────────────────────────────────

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $socialUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')
                ->with('error', 'No se pudo iniciar sesión con Google. Intenta de nuevo.');
        }

        return $this->loginOrCreate($socialUser);
    }

    // ─── Facebook ─────────────────────────────────────────────────────────────────

    public function redirectToFacebook()
    {
        return Socialite::driver('facebook')->redirect();
    }

    public function handleFacebookCallback()
    {
        try {
            $socialUser = Socialite::driver('facebook')->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')
                ->with('error', 'No se pudo iniciar sesión con Facebook. Intenta de nuevo.');
        }

        return $this->loginOrCreate($socialUser);
    }

    // ─── Shared logic ─────────────────────────────────────────────────────────────

    private function loginOrCreate(\Laravel\Socialite\Contracts\User $socialUser)
    {
        $user = User::firstOrCreate(
            ['email' => $socialUser->getEmail()],
            [
                'name'              => $socialUser->getName() ?? 'Usuario',
                'password'          => bcrypt(Str::random(32)),
                'avatar'            => $socialUser->getAvatar(),
                'email_verified_at' => now(),
            ]
        );

        Auth::login($user, remember: true);

        if (! $user->onboarding_completed_at) {
            return redirect()->route('onboarding');
        }

        return redirect()->route('dashboard');
    }
}

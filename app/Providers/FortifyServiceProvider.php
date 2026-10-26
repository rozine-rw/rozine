<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Domain\Identity\SandboxNotice;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureMail();
        $this->configureRateLimiting();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => Inertia::render('auth/login', [
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'status' => $request->session()->get('status'),
            'audience' => self::audience($request),
        ]));

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/reset-password', [
            'email' => $request->email,
            'token' => $request->route('token'),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('auth/forgot-password', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::verifyEmailView(fn (Request $request) => Inertia::render('auth/verify-email', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::registerView(fn (Request $request) => Inertia::render('auth/register', [
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
            'sandboxNotice' => SandboxNotice::toArray(),
            'audience' => self::audience($request),
        ]));

        Fortify::twoFactorChallengeView(fn () => Inertia::render('auth/two-factor-challenge'));

        Fortify::confirmPasswordView(fn () => Inertia::render('auth/confirm-password'));
    }

    /**
     * The app a visitor is signing in to, which only brands the shared log in and register pages:
     * the entry link's `for` query, else the app of the page they were sent away from. The form,
     * fields and account are the same for every app.
     *
     * @return 'business'|'investor'|'auditor'|null
     */
    private static function audience(Request $request): ?string
    {
        $audiences = ['business', 'investor', 'auditor'];
        $chosen = $request->query('for');

        if (is_string($chosen) && in_array($chosen, $audiences, true)) {
            return $chosen;
        }

        $intended = $request->session()->get('url.intended');
        $segment = is_string($intended) ? explode('/', trim((string) parse_url($intended, PHP_URL_PATH), '/'))[0] : '';

        return in_array($segment, $audiences, true) ? $segment : null;
    }

    /**
     * Render Fortify's verification and password reset emails with the Rozine mail templates.
     */
    private function configureMail(): void
    {
        VerifyEmail::toMailUsing(fn (User $user, string $url): MailMessage => (new MailMessage)
            ->subject(__('Verify your email address'))
            ->action(__('Verify email address'), $url)
            ->markdown('mail.account.verify-email', [
                'name' => $user->name,
                'url' => $url,
                'expiresInMinutes' => config()->integer('auth.verification.expire', 60),
            ]));

        ResetPassword::toMailUsing(function (User $user, string $token): MailMessage {
            $broker = config()->string('auth.defaults.passwords');
            $url = url(route('password.reset', ['token' => $token, 'email' => $user->getEmailForPasswordReset()], false));

            return (new MailMessage)
                ->subject(__('Reset your Rozine password'))
                ->action(__('Reset password'), $url)
                ->markdown('mail.account.reset-password', [
                    'email' => $user->getEmailForPasswordReset(),
                    'url' => $url,
                    'expiresInMinutes' => config()->integer("auth.passwords.{$broker}.expire"),
                ]);
        });
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('passkeys', function (Request $request) {
            return Limit::perMinute(10)->by(
                ($request->input('credential.id') ?: $request->session()->getId()).'|'.$request->ip(),
            );
        });
    }
}

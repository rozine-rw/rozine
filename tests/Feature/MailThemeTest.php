<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\Investor\ReportPublished;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Mail\Markdown;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * The Rozine mail theme: the shared components every email renders through, Fortify's own
 * verification and reset emails rendered with them, and the local mail preview route.
 */
function mailThemeRecipient(): User
{
    return (new User)->forceFill(['id' => 42, 'name' => 'Aline Uwase', 'email' => 'aline.uwase@example.test']);
}

function mailThemeText(MailMessage $mail): string
{
    return (string) app(Markdown::class)->renderText((string) $mail->markdown, $mail->data());
}

test('the verification email uses the Rozine template and keeps the signed link', function () {
    $mail = (new VerifyEmail)->toMail(mailThemeRecipient());
    $html = (string) $mail->render();

    expect($mail->subject)->toBe('Verify your email address')
        ->and($mail->markdown)->toBe('mail.account.verify-email')
        ->and($mail->viewData['url'])->toContain('/email/verify/42/')->toContain('signature=')
        ->and($mail->actionUrl)->toBe($mail->viewData['url'])
        ->and($html)->toContain('Verify your email')
        ->and($html)->toContain('Hi Aline Uwase')
        ->and($html)->toContain('This link expires in 60 minutes.')
        ->and($html)->toContain('button-cell-primary')
        ->and($html)->toContain('images/rozine-star-white.png')
        ->and($html)->toContain('Rozine Technologies Ltd')
        ->and($html)->not->toContain('Regards')
        ->and($html)->not->toContain('All rights reserved');
});

test('the password reset email links to the reset page for that address', function () {
    config(['auth.passwords.users.expire' => 45]);

    $mail = (new ResetPassword('reset-token-123'))->toMail(mailThemeRecipient());
    $text = mailThemeText($mail);

    expect($mail->subject)->toBe('Reset your Rozine password')
        ->and($mail->viewData['url'])->toBe(url('/reset-password/reset-token-123?email=aline.uwase%40example.test'))
        ->and($mail->actionUrl)->toBe($mail->viewData['url'])
        ->and($text)->toContain('reset the password for aline.uwase@example.test')
        ->and($text)->toContain('This link expires in 45 minutes.')
        ->and($text)->toContain('Reset password: '.url('/reset-password/reset-token-123?email=aline.uwase%40example.test'));
});

test('the theme inlines the Rozine product tokens', function () {
    $html = (string) (new VerifyEmail)->toMail(mailThemeRecipient())->render();

    expect($html)->toContain('background-color: #f4f7fc')
        ->and($html)->toContain('background-color: #1e3aff')
        ->and($html)->toContain('border-radius: 16px')
        ->and($html)->toContain('color: #0c1830');
});

test('a production email carries no environment notice', function () {
    $mail = (new VerifyEmail)->toMail(mailThemeRecipient());

    expect((string) $mail->render())->not->toContain('not live')
        ->and(mailThemeText($mail))->not->toContain('not live');
});

test('a demo or UAT email opens with the same non-live notice the app shows', function (string $environment, string $label) {
    app()->detectEnvironment(fn (): string => $environment);

    $mail = (new VerifyEmail)->toMail(mailThemeRecipient());

    expect((string) $mail->render())->toContain($label)
        ->toContain('Use synthetic data only. No real-money transactions.')
        ->and(mailThemeText($mail))->toStartWith($label.': Use synthetic data only.');
})->with([
    'demo' => ['demo', 'Demo — not live'],
    'staging' => ['staging', 'UAT — not live'],
]);

test('values echoed into an email cannot become links or markup', function () {
    $mail = (new ReportPublished('[Claim your prize](https://evil.example) <b>Ltd</b>', 'September 2026', 'Jean Habimana', 'https://rozine.test/investor'))
        ->toMail(mailThemeRecipient());
    $html = (string) $mail->render();

    expect($html)->not->toContain('href="https://evil.example"')
        ->and($html)->not->toContain('<b>Ltd</b>')
        ->and($html)->toContain('[Claim your prize](https://evil.example) &lt;b&gt;Ltd&lt;/b&gt;');
});

test('the mail preview lists every template', function () {
    $templates = array_keys(require resource_path('fixtures/mail.php'));

    $response = $this->get('/preview/mail')->assertOk();

    foreach ($templates as $template) {
        $response->assertSee(url("preview/mail/{$template}"), false);
    }
});

test('every mail template renders in the preview with Rozine branding', function (string $template) {
    $this->get("/preview/mail/{$template}")
        ->assertOk()
        ->assertSee('rozine')
        ->assertSee('Rozine Technologies Ltd')
        ->assertSee('images/rozine-star-white.png', false);
})->with(fn (): array => array_keys(require dirname(__DIR__, 2).'/resources/fixtures/mail.php'));

test('an unknown mail template is not found', function () {
    $this->withoutVite();

    $this->get('/preview/mail/account.missing')->assertNotFound();
});

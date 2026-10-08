<?php

declare(strict_types=1);

namespace App\Models;

use App\Application\Identity\EmailVerificationCode;
use App\Notifications\Account\OneTimeCode;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property string|null $party_id
 * @property string|null $active_membership_id
 * @property int|null $active_membership_revision
 * @property int $context_revision
 * @property-read Party|null $party
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token', 'party_id', 'party', 'active_membership_id', 'active_membership_revision', 'context_revision'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /** @var array<string, mixed> */
    protected $attributes = ['context_revision' => 0];

    /** @return BelongsTo<Party, $this> */
    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    /**
     * Mark the email verified only while the account still holds the address the code was sent
     * to, in one conditional write, so an email change that lands between the code check and
     * this write cannot inherit the proof. Returns whether this call verified the address.
     */
    public function markEmailAsVerifiedFor(string $email): bool
    {
        $verifiedAt = $this->freshTimestamp();

        $verified = static::query()
            ->whereKey($this->getKey())
            ->where('email', $email)
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => $verifiedAt]) === 1;

        if ($verified) {
            $this->forceFill(['email_verified_at' => $verifiedAt])->syncOriginalAttribute('email_verified_at');
        }

        return $verified;
    }

    /** Whether the account, as loaded, is verified at exactly this address. */
    public function isVerifiedAt(string $email): bool
    {
        return $this->hasVerifiedEmail() && $this->getEmailForVerification() === $email;
    }

    /**
     * Replace the account's address and drop any proof of the old one in a single write, so a
     * verification of the old address that commits while this request runs cannot carry over.
     */
    public function changeEmail(string $email): void
    {
        static::query()->whereKey($this->getKey())->update(['email' => $email, 'email_verified_at' => null]);

        $this->forceFill(['email' => $email, 'email_verified_at' => null])->syncOriginalAttributes(['email', 'email_verified_at']);
    }

    /**
     * Confirm the email address with a six-digit code rather than a link. Sign-up and every
     * "send a new code" request issue a fresh code, which replaces the last one.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new OneTimeCode(
            app(EmailVerificationCode::class)->issue($this->id, $this->getEmailForVerification()),
            EmailVerificationCode::EXPIRES_IN_MINUTES,
        ));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'active_membership_revision' => 'integer',
            'context_revision' => 'integer',
        ];
    }
}

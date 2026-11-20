<?php

declare(strict_types=1);

namespace App\Application\Pulse;

use App\Application\Pulse\Contracts\PulseSignupRepository;
use App\Enums\PulseSignupType;
use Illuminate\Support\Str;

/**
 * Put someone on the landing page waitlist. The landing page asks only for a
 * name and a phone number or email, so the signup carries no pledge or figures;
 * it records their place in the queue and the link they can share.
 */
final class RegisterSiteWaitlist
{
    public function __construct(private PulseSignupRepository $signups) {}

    /**
     * @param  array{name: string, contact_method: string, contact: string, referred_by: string|null, ip_address: string|null, user_agent: string|null}  $signup
     * @return array{queue_number: string, position: int, referral_code: string}
     */
    public function handle(array $signup, PulseSignupType $type): array
    {
        $created = $this->signups->createWaitlistSignup($signup, $type, self::referralStem($signup['name']));

        return [
            'queue_number' => $created['queue_number'],
            'position' => (int) preg_replace('/\D/', '', $created['queue_number']),
            'referral_code' => $created['referral_code'],
        ];
    }

    /**
     * The readable start of a referral code: the first word of the name, in plain
     * lowercase letters and digits.
     */
    public static function referralStem(string $name): string
    {
        $first = Str::of(Str::ascii($name))->trim()->explode(' ')->first() ?? '';
        $stem = Str::lower((string) preg_replace('/[^A-Za-z0-9]/', '', (string) $first));

        return Str::limit($stem === '' ? 'rozine' : $stem, 24, '');
    }
}

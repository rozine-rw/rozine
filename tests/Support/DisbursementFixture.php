<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Application\Disbursement\Contracts\SyntheticDisbursementFixtures;
use App\Application\Disbursement\Contracts\SyntheticPayoutScripts;
use App\Application\Disbursement\FundedCampaign;
use App\Application\Disbursement\ManageDisbursements;
use App\Application\Disbursement\OpenFundedDisbursements;
use App\Application\Identity\ConfigureStaffAccess;
use App\Models\Disbursement;
use App\Models\DisbursementIntent;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * Synthetic S3-D scenarios: dedicated staff accounts with their own authenticator secrets, a
 * synthetic funded campaign opened as a disbursement, and the maker/checker steps. Nothing here is
 * production funding authority.
 */
final class DisbursementFixture
{
    /** @var array<int, string> */
    private static array $secrets = [];

    /** @param list<string> $roles */
    public static function staff(array $roles): User
    {
        $secret = (new Google2FA)->generateSecretKey();
        $user = User::factory()->create();
        $user->forceFill(['two_factor_secret' => encrypt($secret), 'two_factor_confirmed_at' => now()->subMinute()])->save();
        app(ConfigureStaffAccess::class)->handle($user->id, true, 'Synthetic disbursement staff.', (string) Str::uuid(), $roles);
        self::$secrets[$user->id] = $secret;

        return $user;
    }

    /** A current authenticator code for this staff member, cleared from Fortify's reuse cache. */
    public static function code(User $user): string
    {
        $code = (new Google2FA)->getCurrentOtp(self::$secrets[$user->id]);
        Cache::forget('fortify.2fa_codes.'.md5($code));

        return $code;
    }

    public static function sources(): SyntheticDisbursementFixtures
    {
        return app(SyntheticDisbursementFixtures::class);
    }

    public static function provider(): SyntheticPayoutScripts
    {
        return app(SyntheticPayoutScripts::class);
    }

    /** @return array{campaign: FundedCampaign, disbursement: Disbursement} */
    public static function funded(): array
    {
        $campaign = self::sources()->fund();
        app(OpenFundedDisbursements::class)->handle();

        return ['campaign' => $campaign, 'disbursement' => Disbursement::query()->where('business_campaign_id', $campaign->campaignId)->sole()];
    }

    /** @return array<string, mixed> */
    public static function command(User $actor, Disbursement $disbursement, string $command, int $revision, ?string $proof = null, ?string $requestId = null, string $reason = 'Checked against the funded campaign.'): array
    {
        return app(ManageDisbursements::class)->command($actor->id, $disbursement->id, $command, $revision, $reason, $requestId ?? (string) Str::uuid(), $proof);
    }

    /** @return array<string, mixed> the disbursement detail as this staff member reads it */
    public static function detail(User $viewer, Disbursement $disbursement): array
    {
        return app(ManageDisbursements::class)->page($viewer->id, $disbursement->id, null, 25)['disbursement'];
    }

    /** @return array{proof: string, expires_at: string} */
    public static function stepUp(User $checker, Disbursement $disbursement): array
    {
        $detail = self::detail($checker, $disbursement);

        return app(ManageDisbursements::class)->stepUp($checker->id, $disbursement->id, $detail['revision'], $detail['approval_binding']['intent_digest'], self::code($checker));
    }

    /**
     * Authorized by a treasury maker and approved by an approver checker, with the payout sent.
     *
     * @return array{campaign: FundedCampaign, disbursement: Disbursement, maker: User, checker: User, intent: DisbursementIntent, approval: array<string, mixed>}
     */
    public static function approved(): array
    {
        ['campaign' => $campaign, 'disbursement' => $disbursement] = self::funded();
        $maker = self::staff(['treasury']);
        $checker = self::staff(['approver']);
        self::command($maker, $disbursement, 'authorize', 0);
        $approval = self::command($checker, $disbursement, 'approve', 1, self::stepUp($checker, $disbursement)['proof']);

        return ['campaign' => $campaign, 'disbursement' => $disbursement, 'maker' => $maker, 'checker' => $checker,
            'intent' => DisbursementIntent::query()->where('disbursement_id', $disbursement->id)->sole(), 'approval' => $approval];
    }
}

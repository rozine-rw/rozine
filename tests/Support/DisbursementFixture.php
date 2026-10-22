<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Application\Disbursement\Contracts\SyntheticDisbursementFixtures;
use App\Application\Disbursement\Contracts\SyntheticPayoutScripts;
use App\Application\Disbursement\FundedCampaign;
use App\Application\Disbursement\ManageDisbursements;
use App\Application\Disbursement\OpenFundedDisbursements;
use App\Application\Disbursement\RecordPayoutEvent;
use App\Models\Disbursement;
use App\Models\DisbursementClosing;
use App\Models\DisbursementIntent;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * Synthetic S3-D scenarios: dedicated staff accounts with their own authenticator secrets, and a
 * synthetic funded campaign opened as a disbursement, which pays automatically (BRS v2.0 SVC-R01)
 * unless something blocks it. Nothing here is production funding authority.
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
        StaffFixture::provision($user, $roles, 'Synthetic disbursement staff.');
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

    /**
     * A funded campaign whose disbursement is opened but not paid: its payout destination is not
     * verified, so automatic payment waits. `release()` verifies it and runs payment again.
     *
     * @return array{campaign: FundedCampaign, disbursement: Disbursement}
     */
    public static function funded(): array
    {
        $campaign = self::sources()->fund();
        self::sources()->setDestination($campaign->businessId, 'revoked');
        app(OpenFundedDisbursements::class)->handle();

        return ['campaign' => $campaign, 'disbursement' => Disbursement::query()->where('business_campaign_id', $campaign->campaignId)->sole()];
    }

    /** Verifies the campaign's destination again and runs automatic payment. */
    public static function release(FundedCampaign $campaign): void
    {
        self::sources()->setDestination($campaign->businessId, 'rotated');
        app(OpenFundedDisbursements::class)->handle();
    }

    /** @return array<string, mixed> */
    public static function command(User $actor, Disbursement $disbursement, string $command, int $revision, ?string $requestId = null, string $reason = 'Checked against the funded campaign.'): array
    {
        return app(ManageDisbursements::class)->command($actor->id, $disbursement->id, $command, $revision, $reason, $requestId ?? (string) Str::uuid());
    }

    /** @return array<string, mixed> the disbursement detail as this staff member reads it */
    public static function detail(User $viewer, Disbursement $disbursement): array
    {
        return app(ManageDisbursements::class)->page($viewer->id, $disbursement->id, null, 25)['disbursement'];
    }

    /**
     * Funded and paid automatically: the system recorded the intent and the payout was sent.
     *
     * @return array{campaign: FundedCampaign, disbursement: Disbursement, intent: DisbursementIntent}
     */
    public static function paid(): array
    {
        $campaign = self::sources()->fund();
        app(OpenFundedDisbursements::class)->handle();
        $disbursement = Disbursement::query()->where('business_campaign_id', $campaign->campaignId)->sole();

        return ['campaign' => $campaign, 'disbursement' => $disbursement, 'intent' => DisbursementIntent::query()->where('disbursement_id', $disbursement->id)->sole()];
    }

    /** A real issued disbursement closing, as the cause an issue posting must name. */
    public static function issuedClosing(): string
    {
        ['intent' => $intent] = self::paid();
        app(RecordPayoutEvent::class)->handle(self::provider()->callback($intent->id, 'succeeded'));

        return DisbursementClosing::query()->where('intent_id', $intent->id)->where('kind', 'issued')->sole()->id;
    }
}

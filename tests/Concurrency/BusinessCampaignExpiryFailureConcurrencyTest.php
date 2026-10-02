<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Business\Contracts\BusinessExposureStore;
use App\Models\BusinessCampaign;
use App\Models\BusinessCampaignClosure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use Tests\Support\AuditSealingFixture;
use Tests\Support\PrimaryReservationFixture;

it('isolates a failure at the outer PostgreSQL commit and retains later campaign progress across reconnect', function (): void {
    $this->freezeSecond();
    PrimaryReservationFixture::campaign();
    $other = AuditSealingFixture::ready();
    $totp = new Google2FA;
    $secret = $totp->generateSecretKey();
    $other['user']->forceFill(['two_factor_secret' => encrypt($secret)])->save();
    $other['code'] = $totp->getCurrentOtp($secret);
    AuditSealingFixture::seal($other);
    AuditSealingFixture::cosign($other);
    $store = app(BusinessCampaignStore::class);
    $store->release($other['audit']['staff']->id, $other['application']->id, 0, 'Reviewed.', (string) Str::uuid());
    $store->publish($other['audit']['authority']['users'][0]->id, 1, $other['audit']['business'], $other['application']->id,
        $other['application']->refresh()->revision, 'listing-fee-waiver-1', (string) Str::uuid());
    [$first, $second] = BusinessCampaign::query()->orderBy('expires_at')->orderBy('id')->get()->all();
    $this->travelTo($second->expires_at);
    DB::select("SELECT set_config('rozine.test_expiry_campaign', ?, false)", [$first->id]);
    DB::unprepared(<<<'SQL'
        CREATE FUNCTION synthetic_campaign_expiry_commit_failure() RETURNS trigger LANGUAGE plpgsql AS $$
        BEGIN
            IF NEW.business_campaign_id = current_setting('rozine.test_expiry_campaign', true) THEN
                RAISE EXCEPTION 'synthetic deferred closure failure' USING ERRCODE = '23514';
            END IF;
            RETURN NULL;
        END; $$;
        CREATE CONSTRAINT TRIGGER synthetic_campaign_expiry_commit_failure
            AFTER INSERT ON business_campaign_closures DEFERRABLE INITIALLY DEFERRED
            FOR EACH ROW EXECUTE FUNCTION synthetic_campaign_expiry_commit_failure();
        SQL);
    try {
        expect(fn () => $store->expireDue(1))->toThrow(PDOException::class, 'synthetic deferred closure failure')
            ->and(DB::transactionLevel())->toBe(0);
        DB::purge();
        expect(BusinessCampaignClosure::query()->sole()->business_campaign_id)->toBe($second->id)
            ->and(app(BusinessExposureStore::class)->current($first->business_id))->toHaveCount(1)
            ->and(app(BusinessExposureStore::class)->current($second->business_id))->toBe([]);
        $failure = DB::table('business_campaign_expiry_failures')->sole();
        expect($failure->business_campaign_id)->toBe($first->id)
            ->and($failure->exception_class)->toBe(PDOException::class)
            ->and($failure->reason_code)->toBe('UNCLASSIFIED_EXPIRY_FAILURE');
    } finally {
        DB::unprepared('DROP TRIGGER synthetic_campaign_expiry_commit_failure ON business_campaign_closures;
            DROP FUNCTION synthetic_campaign_expiry_commit_failure();');
    }
    expect(app(BusinessCampaignStore::class)->expireDue(1))->toBe(1)
        ->and(app(BusinessCampaignStore::class)->expireDue(1))->toBe(0);
    DB::purge();
    expect(BusinessCampaignClosure::query()->count())->toBe(2)
        ->and(DB::table('business_campaign_expiry_failures')->count())->toBe(1)
        ->and(app(BusinessExposureStore::class)->current($first->business_id))->toBe([]);
});

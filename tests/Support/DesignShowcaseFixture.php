<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Application\Auditor\ConfirmAuditStepUp;
use App\Application\Auditor\CosignAuditReport;
use App\Application\Auditor\GetAuditProcedure;
use App\Application\Auditor\RequestAuditAssignment;
use App\Application\Auditor\SaveAuditReportStep;
use App\Application\Auditor\SealAuditReport;
use App\Application\Auditor\SetAuditorAvailability;
use App\Application\Auditor\StartAuditReport;
use App\Application\Auditor\SubmitAuditorAccreditation;
use App\Application\Business\ConfigureBusinessAuthority;
use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Business\CreateBusinessApplication;
use App\Application\Business\EvaluateBusinessApplication;
use App\Application\Business\GetBusinessApplicationReview;
use App\Application\Business\SaveBusinessApplication;
use App\Application\Business\SubmitBusinessApplication;
use App\Application\Disbursement\Contracts\SyntheticDisbursementFixtures;
use App\Application\Disbursement\OpenFundedDisbursements;
use App\Application\Evidence\IngestStatement;
use App\Application\Evidence\RecordStatementTranscription;
use App\Application\Evidence\RecordStatementVerification;
use App\Application\Identity\ConfigureStaffAccess;
use App\Application\Identity\SaveInvestorVerification;
use App\Application\Identity\SelectActiveRole;
use App\Application\Identity\SubmitInvestorVerification;
use App\Application\Identity\UploadInvestorVerificationDocument;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryFunding;
use App\Application\Pulse\RegisterPulseBusiness;
use App\Application\Pulse\RegisterPulseInvestor;
use App\Application\Wallet\ApplyProviderOutcome;
use App\Application\Wallet\Contracts\SyntheticWalletFixtures;
use App\Application\Wallet\DispatchDepositIntents;
use App\Domain\Business\MandateAuthority;
use App\Domain\Pulse\PulseSector;
use App\Models\AuditAssignment;
use App\Models\AuditReport;
use App\Models\AuditSigningKey;
use App\Models\BusinessApplication;
use App\Models\BusinessCampaign;
use App\Models\DepositPolicy;
use App\Models\InvestorFundingMethod;
use App\Models\InvestorVerification;
use App\Models\Party;
use App\Models\PrimaryReservationVersion;
use App\Models\RoleMembership;
use App\Models\StatementEvidence;
use App\Models\StatementTranscription;
use App\Models\StatementVerification;
use App\Models\User;
use App\Models\VerifiedOrganizationIdentity;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use PragmaRX\Google2FA\Google2FA;

/**
 * Builders for the opt-in design showcase pack (`Database\Seeders\DesignShowcaseSeeder`): named people,
 * Businesses and Audit Partners taken through the real application actions, the synthetic local wallet
 * and disbursement hooks, and the other acceptance fixtures. Names and requested figures are inputs; every
 * quote, rating, balance, seal and progress figure is what the domain computes from them.
 *
 * @phpstan-type Person array{name: string, email: string}
 * @phpstan-type Business array{name: string, code: string, industry: string, district: string, established_year: int, people: list<Person>, inflow: string, outflow: string}
 * @phpstan-type Raise array{title: string, target: string, term_months: int, use_of_funds: list<string>, story: string}
 * @phpstan-type Built array{id: string, name: string, users: list<User>, transcription: string, source: string, csv_sha256: string, closing_balance: string}
 * @phpstan-type Account array{user: User, secret: string|null}
 */
final class DesignShowcaseFixture
{
    /**
     * A dedicated staff account with its own authenticator, granted these console roles.
     *
     * @param  list<string>  $roles
     * @return array{user: User, secret: string}
     */
    public static function staff(string $name, string $email, string $password, array $roles): array
    {
        $secret = (new Google2FA)->generateSecretKey();
        $user = User::factory()->create(['name' => $name, 'email' => $email, 'password' => $password,
            'two_factor_secret' => encrypt($secret), 'two_factor_recovery_codes' => encrypt('[]'), 'two_factor_confirmed_at' => now()->subMinutes(10)]);
        self::must(app(ConfigureStaffAccess::class)->handle($user->id, true, 'Synthetic design showcase staff.', (string) Str::uuid(), $roles), 'STAFF_ACCESS_ENABLED');

        return ['user' => $user, 'secret' => $secret];
    }

    /** The consent release and current seal key every application and audit needs, unless already present. */
    public static function catalogs(User $compliance): void
    {
        if (! DB::table('consent_releases')->exists()) {
            self::must(ConsentFixture::record($compliance), 'CONSENT_RELEASE_RECORDED');
        }
        if (! AuditSigningKey::query()->where('valid_from', '<=', now('UTC'))->where('rotate_at', '>', now('UTC'))->exists()) {
            AuditSigningKey::factory()->create();
        }
    }

    /**
     * An Audit Partner with an authenticator. Approved partners are accepting work, have accepted the
     * engagement terms and have a verified office; the others wait in the accreditation queue.
     *
     * @return array{user: User, secret: string}
     */
    public static function auditor(string $name, string $email, string $password, string $licence, string $expires, User $compliance, bool $approved): array
    {
        ['user' => $user, 'secret' => $secret] = self::person($name, $email, $password, 'auditor', true);
        $submitted = self::must(app(SubmitAuditorAccreditation::class)->handle($user->id, 1, 0, $licence, $expires,
            'practising-certificate.pdf', "%PDF-1.7\nSynthetic practising certificate for the design showcase\n%%EOF", (string) Str::uuid()), 'ACCREDITATION_SUBMITTED');
        if ($approved) {
            self::must(AuditorFixture::review($compliance, (string) $user->party_id, 1, 'approve', $submitted['data']['submission_id']), 'ACCREDITATION_REVIEWED');
            app(SetAuditorAvailability::class)->handle($user->id, 1, 2, true, (string) Str::uuid());
            AuditEngagementFixture::ready($compliance, $user);
            AuditAssignmentFixture::location($compliance, 'office', (string) $user->party_id);
        }

        return ['user' => $user, 'secret' => (string) $secret];
    }

    /**
     * A verified Investor whose deposits are each recorded, dispatched and confirmed by a signed
     * synthetic provider event, exactly as `local:wallet --seed` and `--event` do.
     *
     * @param  list<string>  $deposits
     */
    public static function investor(string $name, string $email, string $password, array $deposits): User
    {
        ['user' => $user] = InvestorWalletFixture::investor($email, $password);
        $user->forceFill(['name' => $name])->save();
        if (! DepositPolicy::query()->where('status', 'active')->exists()) {
            InvestorWalletFixture::policy(maximum: null);
        }
        $fixtures = app(SyntheticWalletFixtures::class);
        $method = InvestorFundingMethod::query()->whereKey($fixtures->prepareInvestor((string) $user->party_id)['method_id'])->sole();
        foreach ($deposits as $amount) {
            $requestId = (string) Str::uuid();
            self::must(InvestorWalletFixture::deposit(['user' => $user, 'method' => $method], $amount, $requestId), 'DEPOSIT_INTENT_RECORDED');
            $event = $fixtures->event($requestId, 'succeeded', null, null);
            app(DispatchDepositIntents::class)->handle($event['intent_id']);
            $outcome = app(ApplyProviderOutcome::class)->handle($event['message']);
            if (! $outcome['credited']) {
                throw new LogicException('DESIGN_SHOWCASE_DEPOSIT_NOT_CREDITED: '.$outcome['disposition']);
            }
        }

        return $user->refresh();
    }

    /** A person who has submitted identity documents that Compliance has not reviewed yet. */
    public static function pendingInvestor(string $name, string $email, string $password, string $dateOfBirth, string $nationalId): User
    {
        $user = User::factory()->create(['party_id' => Party::factory(), 'name' => $name, 'email' => $email, 'password' => $password]);
        $steps = [['personal', ['date_of_birth' => $dateOfBirth]], 'id_front', 'id_back',
            ['document', ['id_type' => 'national_id', 'id_number' => $nationalId]], 'selfie'];
        foreach ($steps as $step) {
            $revision = (int) InvestorVerification::query()->where('party_id', $user->party_id)->value('revision');
            is_array($step)
                ? app(SaveInvestorVerification::class)->handle($user->id, 0, $revision, $step[0], $step[1], (string) Str::uuid())
                : app(UploadInvestorVerificationDocument::class)->handle($user->id, 0, $revision, $step, $step.'.png', self::identityImage($step), (string) Str::uuid());
        }
        $revision = (int) InvestorVerification::query()->where('party_id', $user->party_id)->value('revision');
        self::must(app(SubmitInvestorVerification::class)->handle($user->id, 0, $revision, (string) Str::uuid()), 'VERIFICATION_SUBMITTED');

        return $user->refresh();
    }

    /**
     * Configures the Business's mandate under its own name (the first person is the required signatory),
     * verifies its premises, records the Audit Partner's independence and the 36 months of statements
     * the quote is sized from.
     *
     * @param  Business  $spec
     * @return Built
     */
    public static function business(array $spec, string $password, User $operations, User $auditor): array
    {
        $users = $members = [];
        foreach ($spec['people'] as $person) {
            ['user' => $user] = self::person($person['name'], $person['email'], $password, 'business');
            $users[] = $user;
            $members[] = ['party_id' => (string) $user->party_id, 'name' => $person['name'], 'roles' => ['owner', 'controller', 'signatory'],
                'permissions' => MandateAuthority::PERMISSIONS];
        }
        $entity = VerifiedOrganizationIdentity::factory()->create(['registry_digest' => hash('sha256', 'RDB:'.$spec['code'])])->party_id;
        $configured = self::must(app(ConfigureBusinessAuthority::class)->handle($operations->id, 'organization', (string) $entity,
            ['name' => $spec['name'], 'company_code' => $spec['code'], 'industry' => $spec['industry'], 'district' => $spec['district'], 'established_year' => $spec['established_year']],
            ['people' => $members, 'required_signatories' => [$members[0]['party_id']], 'effective_at' => now('UTC')->subMinute()->format('Y-m-d\TH:i:s\Z'),
                'expires_at' => null, 'status' => 'active', 'attested_complete' => true],
            0, 'synthetic:design-showcase-mandate', 'Reviewed complete authority evidence.', (string) Str::uuid()), 'BUSINESS_AUTHORITY_RECORDED');
        $id = (string) $configured['data']['business']['id'];
        AuditAssignmentFixture::location($operations, 'premises', $id);
        AuditAssignmentFixture::independence($operations, $id, (string) $auditor->party_id);

        $owner = $users[0];
        $first = now('Africa/Kigali')->toImmutable()->startOfMonth()->subMonths(36);
        $csv = "date,reference,amount\n";
        for ($index = 0; $index < 36; $index++) {
            $day = $first->addMonths($index)->format('Y-m-d');
            $csv .= "{$day},sales,{$spec['inflow']}\n{$day},costs,-{$spec['outflow']}\n";
        }
        $ingested = self::must(app(IngestStatement::class)->handle($owner->id, self::context($owner), $id, 0, Str::slug($spec['name']).'-statements.csv', $csv, (string) Str::uuid()),
            'INGESTED_NOT_AUDIT_APPROVED');
        $source = (string) $ingested['data']['document_id'];
        $net = (int) $spec['inflow'] - (int) $spec['outflow'];
        $months = $statements = [];
        for ($index = 0; $index < 36; $index++) {
            $date = $first->addMonths($index);
            $months[] = $date->format('Y-m');
            $statements[] = ['rail_id' => 'bank-a', 'month' => $date->format('Y-m'), 'opening_balance' => (string) ($index * $net),
                'closing_balance' => (string) (($index + 1) * $net), 'source_ids' => [$source], 'transactions' => [
                    StatementFixture::transaction('sales', $spec['inflow'], 'operating_inflow', $date->format('Y-m-d'), $source),
                    StatementFixture::transaction('costs', '-'.$spec['outflow'], 'operating_outflow', $date->format('Y-m-d'), $source),
                ]];
        }
        $transcribed = self::must(app(RecordStatementTranscription::class)->handle($owner->id, self::context($owner), $id, 1,
            [['id' => 'bank-a', 'active_from' => $first->format('Y-m'), 'active_until' => null]], $months, $statements, (string) Str::uuid()),
            'STATEMENT_RECONCILED_UNVERIFIED');

        return ['id' => $id, 'name' => $spec['name'], 'users' => $users, 'transcription' => (string) $transcribed['data']['transcription']['id'],
            'source' => $source, 'csv_sha256' => hash('sha256', $csv), 'closing_balance' => (string) (36 * $net)];
    }

    /**
     * Takes the Business's application as far as `$stage`: offered (the audit offer waits on the Audit
     * Partner), in_progress (accepted, quoted, signed and the field audit started), sealed (awaiting the
     * Business's co-signature) or published (co-signed). A routine audit files a monthly report.
     *
     * @param  Built  $business
     * @param  Raise  $raise
     */
    public static function application(array $business, array $raise, User $operations, User $auditor, User $compliance, string $stage, string $kind = 'flash'): BusinessApplication
    {
        $owner = $business['users'][0];
        $created = self::must(app(CreateBusinessApplication::class)->handle($owner->id, self::context($owner), $business['id'], 0, (string) Str::uuid()), 'APPLICATION_CREATED');
        $application = BusinessApplication::query()->whereKey($created['data']['application']['id'])->sole();
        self::must(app(SaveBusinessApplication::class)->handle($owner->id, self::context($owner), $business['id'], $application->id, $application->revision,
            $raise, 'raise', (string) Str::uuid()), 'APPLICATION_SAVED');
        $requested = self::must(app(RequestAuditAssignment::class)->handle($operations->id, $business['id'], $kind,
            $kind === 'flash' ? 'Pre-listing flash audit.' : 'Monthly field audit.', (string) Str::uuid()), 'AUDIT_ASSIGNMENT_REQUESTED');
        $assignment = AuditAssignment::query()->whereKey($requested['data']['assignment_id'])->sole();
        if ($assignment->party_id !== $auditor->party_id) {
            throw new LogicException('DESIGN_SHOWCASE_DISPATCH_MISMATCH: '.$business['name'].' was not offered to '.$auditor->name.'.');
        }
        if ($stage === 'offered') {
            return $application;
        }

        self::must(AuditAssignmentFixture::respond($auditor, $assignment), 'ASSIGNMENT_ACCEPTED');
        $transcription = StatementTranscription::query()->whereKey($business['transcription'])->sole();
        $evidence = StatementEvidence::query()->where('business_id', $business['id'])->sole();
        $review = StatementFixture::review([$business['source'] => $business['csv_sha256']]);
        $review['recurring_owner_draw'] = '0';
        $review['findings'] = 'Synthetic factual review for the design showcase; no external obligations identified.';
        $assignment->refresh();
        self::must(app(RecordStatementVerification::class)->handle($auditor->id, 1, $assignment->id, $assignment->revision, $evidence->revision,
            (int) StatementVerification::query()->where('statement_evidence_id', $evidence->id)->max('revision'),
            $transcription->id, $transcription->sha256, $review, (string) Str::uuid()), 'STATEMENT_SOURCE_VERIFIED');
        BusinessCreditFactsFixture::record($compliance, $business['id']);
        $application->refresh();
        self::must(app(EvaluateBusinessApplication::class)->handle($owner->id, self::context($owner), $business['id'], $application->id, $application->revision, null, (string) Str::uuid()),
            'APPLICATION_EVALUATED');
        $application->refresh();
        self::must(app(SaveBusinessApplication::class)->handle($owner->id, self::context($owner), $business['id'], $application->id, $application->revision,
            $raise, 'review', (string) Str::uuid()), 'APPLICATION_SAVED');
        $reviewed = app(GetBusinessApplicationReview::class)->handle($owner->id, self::context($owner), $business['id'], $application->id);
        $quote = $reviewed['quote'];
        $acceptance = ['quote_id' => $quote['quote_id'], 'quote_revision' => $quote['quote_revision'], 'evidence_version' => $quote['evidence_version'],
            'mandate_version' => $quote['mandate_version'], 'accepted_principal' => $quote['principal']['amount'],
            'documents' => array_map(fn (array $item): array => array_intersect_key($item, array_flip(['kind', 'version', 'sha256'])), $reviewed['acceptance']['documents']),
            'disclosures' => array_map(fn (array $item): array => array_intersect_key($item, array_flip(['key', 'version', 'sha256'])), $reviewed['acceptance']['disclosures']),
            'terms' => true, 'privacy' => true, 'signature_name' => $owner->name];
        self::must(app(SubmitBusinessApplication::class)->handle($owner->id, self::context($owner), $business['id'], $application->id, $application->refresh()->revision,
            $acceptance, (string) Str::uuid()), 'APPLICATION_SUBMITTED');

        $facts = AuditSourceFactsFixture::facts();
        $facts['photos']['required'][1]['captured_at'] = $facts['check_in']['at'];
        $facts['photos']['required'][1]['position'] = $facts['check_in']['position'];
        AuditSourceFactsFixture::record($operations, $assignment->refresh(), facts: $facts);
        $started = self::must(app(StartAuditReport::class)->handle($auditor->id, 1, $assignment->id, $assignment->refresh()->revision,
            $application->id, $application->refresh()->revision, (string) Str::uuid()), 'AUDIT_REPORT_STARTED');
        $report = AuditReport::query()->whereKey($started['data']['audit_id'])->sole();
        $steps = $kind === 'flash'
            ? ['review' => [], 'check_in' => [], 'photos' => ['titles' => ['extra-1' => 'Stock room']], 'ledger' => ['observed_stock' => '38000000', 'reconciled' => true]]
            : ['statements' => [], 'count' => ['cash' => $business['closing_balance'], 'stock_units' => '190', 'operational_status' => 'active',
                'financial_proofs' => ['bank', 'momo'], 'inventory_proofs' => ['photo']], 'photos' => ['titles' => ['extra-1' => 'Cold room at capacity']]];
        foreach ($stage === 'in_progress' ? array_slice($steps, 0, 1, true) : $steps as $step => $fields) {
            self::must(app(SaveAuditReportStep::class)->handle($auditor->id, 1, $report->id, $report->refresh()->revision, $step, $fields, (string) Str::uuid()),
                'AUDIT_STEP_SAVED');
        }
        if ($stage === 'in_progress') {
            return $application;
        }

        $digest = self::seal($auditor, $report);
        if ($stage === 'published') {
            self::must(app(CosignAuditReport::class)->handle($owner->id, self::context($owner), $business['id'], $report->id, 1, $report->refresh()->revision,
                1, $digest, true, '', (string) Str::uuid()), 'REPORT_PUBLISHED');
        }

        return $application->refresh();
    }

    /**
     * Staff releases the application and the Business's signatory publishes it (listing fee waived).
     *
     * @param  Built  $business
     */
    public static function publish(array $business, BusinessApplication $application, User $operations): BusinessCampaign
    {
        $campaigns = app(BusinessCampaignStore::class);
        self::must($campaigns->release($operations->id, $application->id, 0, 'Verified release.', (string) Str::uuid()), 'APPLICATION_RELEASED');
        self::must($campaigns->publish($business['users'][0]->id, self::context($business['users'][0]), $business['id'], $application->id, $application->refresh()->revision,
            'listing-fee-waiver-1', (string) Str::uuid()), 'LISTING_PUBLISHED');

        return BusinessCampaign::query()->where('business_application_id', $application->id)->sole();
    }

    /**
     * Each purchase is reserved and confirmed through the real checkout under the explicit synthetic
     * admission the Primary fixtures use, since live admission is not activated.
     *
     * @param  list<array{User, string}>  $purchases  investor and units
     */
    public static function commit(BusinessCampaign $campaign, array $purchases): void
    {
        $checkout = app(PrimaryCheckout::class);
        foreach ($purchases as [$investor, $units]) {
            $held = self::must($checkout->reserve($investor->id, self::context($investor), $campaign->id, $units, (string) Str::uuid(), PrimaryReservationFixture::terms(...)), 'RESERVATION_HELD');
            $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $held['data']['reservation_id'])->sole();
            self::must($checkout->confirm($investor->id, self::context($investor), $campaign->id, $held['data']['reservation_id'], 1, $version->payload['terms']['disclosure_version'],
                $version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...)), 'RESERVATION_CONFIRMED');
        }
        PrimaryHoldingFixture::flushDeferredChecks();
    }

    /** Locks a fully committed campaign's funding under the Primary fixtures' synthetic admission. */
    public static function fund(BusinessCampaign $campaign): void
    {
        DB::transaction(fn (): array => app(PrimaryFunding::class)->lock($campaign->id, fn (): array => PrimaryHoldingFixture::admission($campaign)));
    }

    /**
     * Pre-qualified Business sign-ups and Investor pledges through Pulse's own registration, carrying the
     * synthetic provenance the demo reset uses; Pulse sizes each Business itself.
     *
     * @param  list<array{string, string, string, string, int, int, int, int}>  $businesses  name, sector, province, district, annual revenue, annual costs, registered year, term
     * @param  list<array{string, string, string, int}>  $pledges  name, province, district, pledge
     */
    public static function pulse(array $businesses, array $pledges): void
    {
        $signup = fn (string $name, string $province, string $district): array => ['name' => $name, 'contact_method' => 'email',
            'contact' => Str::slug($name, '.').'@example.test', 'province' => $province, 'district' => $district, 'ip_address' => null,
            'user_agent' => 'rozine-synthetic:design-showcase'];
        foreach ($businesses as [$name, $sector, $province, $district, $revenue, $costs, $year, $term]) {
            app(RegisterPulseBusiness::class)->handle($signup($name, $province, $district), $revenue, $costs, PulseSector::from($sector), $year, $term, true, now()->year);
        }
        foreach ($pledges as [$name, $province, $district, $pledge]) {
            app(RegisterPulseInvestor::class)->handle($signup($name, $province, $district), $pledge, config()->integer('pulse.listing_limit'));
        }
    }

    /** The `local:disbursement --seed` scenario: a synthetic funded campaign opened as a disbursement. */
    public static function disbursement(int $termMonths): void
    {
        app(SyntheticDisbursementFixtures::class)->fund([1200, 1200], $termMonths);
        app(OpenFundedDisbursements::class)->handle();
    }

    /**
     * A verified person with this role active, as the identity fixtures create one. A person who already
     * has an account (an Investor who also runs a Business) gains the role and switches to it.
     *
     * @return Account
     */
    private static function person(string $name, string $email, string $password, string $role, bool $mfa = false): array
    {
        $existing = User::query()->where('email', $email)->first();
        if ($existing !== null) {
            RoleMembership::factory()->for(Party::query()->whereKey($existing->party_id)->sole())->active()->create(['role' => $role]);
            app(SelectActiveRole::class)->handle($existing->id, $role, self::context($existing), (string) Str::uuid());

            return ['user' => $existing->refresh(), 'secret' => null];
        }
        $party = Party::factory()->verified()->create();
        $user = User::factory()->for($party)->create(['name' => $name, 'email' => $email, 'password' => $password]);
        $secret = null;
        if ($mfa) {
            $secret = (new Google2FA)->generateSecretKey();
            $user->forceFill(['two_factor_secret' => encrypt($secret), 'two_factor_recovery_codes' => encrypt('[]'),
                'two_factor_confirmed_at' => now()->subMinutes(10)])->save();
        }
        RoleMembership::factory()->for($party)->active()->create(['role' => $role]);
        app(SelectActiveRole::class)->handle($user->id, $role, 0, (string) Str::uuid());

        return ['user' => $user->refresh(), 'secret' => $secret];
    }

    /**
     * A small placeholder picture (no real document or face) for a KYC upload slot, encoded as a PNG
     * without GD: an ID card's front or back, or a selfie silhouette.
     */
    private static function identityImage(string $slot): string
    {
        [$width, $height] = [240, 152];
        $pixel = function (int $x, int $y) use ($slot, $height): string {
            if ($slot === 'selfie') {
                $head = (($x - 120) ** 2) + (($y - 58) ** 2) < 30 ** 2;
                $shoulders = $y > 96 && (($x - 120) ** 2) / 4 + (($y - 152) ** 2) < 55 ** 2;

                return $head || $shoulders ? "\x8a\x94\xa8" : "\xe8\xed\xf7";
            }
            if ($slot === 'id_back') {
                return $y > 104 && $y < 136 && $x > 16 && $x < 224 && $x % 6 < 3 ? "\x16\x20\x2f" : "\xee\xf1\xf7";
            }
            if ($y < 26) {
                return "\x1e\x3a\xff";
            }
            $photo = $x >= 16 && $x < 80 && $y >= 40 && $y < $height - 24;
            $line = $x >= 96 && $x < 224 && in_array(intdiv($y - 44, 18), [0, 1, 2, 3], true) && ($y - 44) % 18 < 8;

            return $photo ? "\xc3\xcb\xda" : ($line ? "\x9a\xa3\xb5" : "\xee\xf1\xf7");
        };
        $rows = '';
        for ($y = 0; $y < $height; $y++) {
            $rows .= "\0";
            for ($x = 0; $x < $width; $x++) {
                $rows .= $pixel($x, $y);
            }
        }
        $chunk = fn (string $type, string $data): string => pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));

        return "\x89PNG\r\n\x1a\n".$chunk('IHDR', pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0)).$chunk('IDAT', (string) gzcompress($rows)).$chunk('IEND', '');
    }

    /** The account's current identity context revision, which every participant command must quote. */
    private static function context(User $user): int
    {
        return $user->refresh()->context_revision;
    }

    /** Seals under the Audit Partner's own authenticator; returns the sealed digest. */
    private static function seal(User $auditor, AuditReport $report): string
    {
        $page = app(GetAuditProcedure::class)->handle($auditor->id, 1, $report->id);
        $preview = $page['seal'];
        $ids = array_values(array_unique([$page['sources']['verification']['id'], $page['sources']['source_facts']['source']['id'],
            ...array_column($page['sources']['documents'], 'id'), ...array_column($page['sources']['ledger_documents'] ?? [], 'id')]));
        sort($ids);
        $report->refresh();
        $code = (new Google2FA)->getCurrentOtp(decrypt((string) $auditor->refresh()->two_factor_secret));
        $proof = app(ConfirmAuditStepUp::class)->handle($auditor->id, 1, $report->id, $report->revision, $preview['digest'], $code);
        Cache::forget('fortify.2fa_codes.'.md5($code));
        self::must(app(SealAuditReport::class)->handle($auditor->id, 1, $report->id, $report->revision,
            ['digest' => $preview['digest'], 'procedure_version' => $preview['payload']['procedure_version'], 'findings_version' => $preview['findings_version'],
                'evidence_version' => $preview['evidence_version'], 'evidence_ids' => $ids, 'note' => $report->draft['note']],
            $proof['proof'], (string) Str::uuid()), 'AUDIT_SEALED');

        return (string) $preview['digest'];
    }

    /**
     * @param  array<string, mixed>  $receipt
     * @return array<string, mixed>
     */
    private static function must(array $receipt, string ...$codes): array
    {
        if (! in_array($receipt['code'] ?? null, $codes, true)) {
            throw new LogicException('DESIGN_SHOWCASE_STEP_FAILED: expected '.implode('|', $codes).', got '.json_encode($receipt['code'] ?? $receipt));
        }

        return $receipt;
    }
}

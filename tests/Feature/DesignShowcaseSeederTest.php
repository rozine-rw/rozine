<?php

declare(strict_types=1);

use App\Application\Environment\EnvironmentIsolation;
use App\Application\Identity\SelectActiveRole;
use App\Models\AuditReport;
use App\Models\User;
use Database\Seeders\DesignShowcaseSeeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    $this->withoutVite();
    $this->storage = sys_get_temp_dir().'/design-showcase-'.Str::lower(Str::random(10));
    File::ensureDirectoryExists($this->storage.'/app');
    $this->originalStorage = storage_path();
    app()->useStoragePath($this->storage);
});

afterEach(function (): void {
    app()->useStoragePath($this->originalStorage);
    File::deleteDirectory($this->storage);
});

/** @return list<string> */
function showcaseCounts(): array
{
    return array_map(fn (string $table): string => $table.'='.DB::table($table)->count(), ['users', 'parties', 'command_operations', 'ledger_lines',
        'business_profiles', 'business_applications', 'business_campaigns', 'audit_reports', 'audit_report_seals', 'primary_commitments',
        'primary_campaign_fundings', 'wallet_deposit_credits', 'investor_verifications', 'disbursements', 'staff_accounts']);
}

function showcaseUser(string $persona): User
{
    $accounts = json_decode((string) file_get_contents(storage_path(DesignShowcaseSeeder::ACCOUNTS)), true, flags: JSON_THROW_ON_ERROR);

    return User::query()->where('email', $accounts['personas'][$persona]['email'])->sole();
}

it('seeds every persona through the domain and each app reads the seeded data back', function (): void {
    $personas = app(DesignShowcaseSeeder::class)->prepare();

    expect(array_keys($personas))->toEqualCanonicalizing(['investor', 'investor_pending', 'business_owner', 'business_cosign', 'auditor', 'compliance', 'operations', 'superadmin']);
    $accounts = json_decode((string) file_get_contents(storage_path(DesignShowcaseSeeder::ACCOUNTS)), true, flags: JSON_THROW_ON_ERROR);
    expect($accounts['personas'])->toBe($personas);
    foreach ($personas as $label => $persona) {
        $user = User::query()->where('email', $persona['email'])->sole();
        expect($persona['email'])->toEndWith('@example.test')
            ->and(Hash::check(DesignShowcaseSeeder::PASSWORD, $user->password))->toBeTrue();
        if (in_array($label, ['auditor', 'compliance', 'operations', 'superadmin'], true)) {
            expect($persona['totp_secret'])->toMatch('/^[A-Z2-7]{16,}$/')->toBe(decrypt((string) $user->two_factor_secret))
                ->and($user->two_factor_confirmed_at)->not->toBeNull();
        } else {
            expect($persona['totp_secret'])->toBeNull();
        }
    }
    expect(User::query()->where('email', 'not like', '%@example.test')->exists())->toBeFalse();

    $investor = showcaseUser('investor');
    expect(showcaseUser('business_owner')->is($investor))->toBeTrue();
    $this->actingAs($investor)->get(route('dashboard'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('dashboard')->where('identity.available_roles', ['investor', 'business']));
    app(SelectActiveRole::class)->handle($investor->id, 'investor', $investor->refresh()->context_revision, (string) Str::uuid());
    $this->actingAs($investor)->get(route('investor.deals'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('investor/deals')->where('gate.status', 'eligible')
            ->where('deals', /** @param Collection<int, array<string, mixed>> $deals */ function (Collection $deals): bool {
                $byName = $deals->keyBy('name');

                return $deals->first()['name'] === 'GreenLeaf Agro'
                    && $byName->keys()->sort()->values()->all() === ['GreenLeaf Agro', 'Huye Supply', 'Kigali Motors', 'Rugali Textiles', 'Umuganda Supply']
                    && $byName['GreenLeaf Agro']['raised']['amount'] === '11900000' && $byName['GreenLeaf Agro']['target']['amount'] === '18000000'
                    && $byName['GreenLeaf Agro']['avg_monthly_revenue']['amount'] === '32000000' && $byName['GreenLeaf Agro']['audited'] === true
                    && $byName['Huye Supply']['funded_pct'] === '100.0';
            }));
    $this->actingAs($investor)->get(route('investor.wallet'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('investor/wallet')
            ->where('wallet.breakdown.available.amount', DesignShowcaseSeeder::INVESTOR_AVAILABLE)
            ->where('wallet.breakdown.committed.amount', '3800000'));
    $umuganda = (string) DB::table('business_campaigns')->where('business_id', DB::table('business_profiles')->where('profile->name', 'Umuganda Supply')->value('id'))->value('id');
    $commitment = (string) DB::table('primary_commitments')->join('primary_reservations', 'primary_reservations.id', '=', 'primary_commitments.primary_reservation_id')
        ->where('primary_reservations.party_id', $investor->party_id)->where('primary_reservations.business_campaign_id', $umuganda)->value('primary_commitments.id');
    $this->actingAs($investor)->get(route('investor.commitments.show', $commitment))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('investor/commitment')->where('refusal', null)->whereNot('commitment', null));
    $this->actingAs(showcaseUser('investor_pending'))->get(route('investor.verification'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('investor/verification')->where('status', 'submitted'));

    $owner = showcaseUser('business_owner');
    app(SelectActiveRole::class)->handle($owner->id, 'business', $owner->refresh()->context_revision, (string) Str::uuid());
    $greenleaf = (string) DB::table('business_profiles')->where('profile->name', 'GreenLeaf Agro')->value('id');
    $this->actingAs($owner)->get(route('business.show', $greenleaf))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('business/home')->where('business.name', 'GreenLeaf Agro')
            ->where('live_raise.title', 'Cold-Chain Hub')->where('live_raise.raised.amount', '11900000')->whereNot('rating', null));
    $this->actingAs($owner)->get(route('business.reports', $greenleaf))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('business/reports')->has('reports.verified', 1)->where('reports.verified.0.period.kind', 'monthly'));
    $sebeya = (string) DB::table('business_profiles')->where('profile->name', 'Sebeya Logistics')->value('id');
    $this->actingAs(showcaseUser('business_cosign'))->get(route('business.show', $sebeya))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('business/home')->where('today.0.kind', 'audit_cosign'));

    $this->actingAs(showcaseUser('auditor'))->get(route('auditor.home'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auditor/home')->where('auditor.name', 'Diane Uwase')->where('nearby.count', 1)
            ->where('in_progress', /** @param Collection<int, array<string, mixed>> $jobs */ fn (Collection $jobs): bool => $jobs->pluck('business')->sort()->values()->all() === ['Murakoze Tech', 'Sebeya Logistics']));
    $this->actingAs(showcaseUser('auditor'))->get(route('auditor.portfolio.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auditor/portfolio')
            ->where('reports', /** @param Collection<int, array<string, mixed>> $reports */ fn (Collection $reports): bool => $reports->pluck('status', 'business')->sortKeys()->all() === ['GreenLeaf Agro' => 'published', 'Sebeya Logistics' => 'awaiting_cosign']));
    $published = AuditReport::query()->where('business_id', $greenleaf)->sole();
    $this->get(route('audit.seals.verify', $published->id))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('audit/verify-seal')->where('seal.seal_status', 'valid'));

    $this->actingAs(showcaseUser('superadmin'))->get(route('staff.dashboard'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/today')
            ->where('kpis', /** @param Collection<int, array<string, mixed>> $kpis */ fn (Collection $kpis): bool => $kpis->firstWhere('key', 'active_businesses')['value']['value'] === 8
                && $kpis->firstWhere('key', 'verified_investors')['value']['value'] === 10));
    $this->actingAs(showcaseUser('compliance'))->get(route('staff.investor-verifications.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/investor-verifications')
            ->where('entries', /** @param Collection<int, array<string, mixed>> $entries */ fn (Collection $entries): bool => $entries->pluck('name')->sort()->values()->all() === ['Patrick Gatera', 'Peace Bizimana', 'Samuel Mugisha']));
    $this->actingAs(showcaseUser('operations'))->get(route('staff.disbursements.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/disbursements')->has('disbursements', 1));
});

it('changes nothing when run again, and only rewrites the sign-in file', function (): void {
    $first = app(DesignShowcaseSeeder::class)->prepare();
    $counts = showcaseCounts();
    File::delete(storage_path(DesignShowcaseSeeder::ACCOUNTS));

    expect(app(DesignShowcaseSeeder::class)->prepare())->toBe($first)
        ->and(showcaseCounts())->toBe($counts)
        ->and(File::exists(storage_path(DesignShowcaseSeeder::ACCOUNTS)))->toBeTrue();
});

it('refuses a pack that an earlier run left part-way, without writing', function (): void {
    User::factory()->create(['email' => 'robert.mugisha@example.test']);

    expect(fn () => app(DesignShowcaseSeeder::class)->prepare())->toThrow(LogicException::class, 'DESIGN_SHOWCASE_INCOMPLETE');
    expect(User::query()->count())->toBe(1)
        ->and(File::exists(storage_path(DesignShowcaseSeeder::ACCOUNTS)))->toBeFalse();
});

it('refuses to seed outside local and testing, before writing anything', function (string $environment): void {
    app()->instance('env', $environment);

    expect(fn () => app(DesignShowcaseSeeder::class)->prepare())->toThrow(LogicException::class);
    expect(User::query()->count())->toBe(0)
        ->and(File::exists(storage_path(DesignShowcaseSeeder::ACCOUNTS)))->toBeFalse();
})->with(['uat', 'staging', 'production']);

it('refuses a demo profile, where the synthetic money hooks are not bound', function (): void {
    app()->instance('env', 'demo');
    $this->mock(EnvironmentIsolation::class, function ($mock): void {
        $mock->shouldReceive('canSeed')->andReturnTrue();
        $mock->shouldReceive('profile')->andReturn('demo');
    });

    expect(fn () => app(DesignShowcaseSeeder::class)->prepare())->toThrow(LogicException::class, 'DESIGN_SHOWCASE_SYNTHETIC_MONEY_UNAVAILABLE');
    expect(User::query()->count())->toBe(0);
});

it('refuses a database that is not a local PostgreSQL server', function (string $key, mixed $value): void {
    config([$key => $value]);

    expect(fn () => app(DesignShowcaseSeeder::class)->assertAllowed())->toThrow(LogicException::class, 'DESIGN_SHOWCASE_LOCAL_DATABASE_REQUIRED');
})->with([
    ['database.connections.pgsql.host', 'database.example.com'],
    ['database.connections.pgsql.url', 'postgresql://user:pass@example.com/rozine'],
    ['database.connections.pgsql.driver', 'sqlite'],
]);

it('keeps the sign-in file out of version control', function (): void {
    $ignored = trim((string) shell_exec('git -C '.escapeshellarg(base_path()).' check-ignore storage/'.escapeshellarg(DesignShowcaseSeeder::ACCOUNTS)));

    expect($ignored)->toBe('storage/'.DesignShowcaseSeeder::ACCOUNTS);
});

<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Auditor\Contracts\AuditAssignmentStore;
use App\Application\Auditor\Contracts\AuditEngagementStore;
use App\Application\Auditor\Contracts\AuditLedgerEvidence;
use App\Application\Auditor\Contracts\AuditLedgerExtractionQueue;
use App\Application\Auditor\Contracts\AuditLocationStore;
use App\Application\Auditor\Contracts\AuditorIndependenceStore;
use App\Application\Auditor\Contracts\AuditorProfileStore;
use App\Application\Auditor\Contracts\AuditReportCryptography;
use App\Application\Auditor\Contracts\AuditReportLifecycle;
use App\Application\Auditor\Contracts\AuditReportPublicationStore;
use App\Application\Auditor\Contracts\AuditReportStore;
use App\Application\Auditor\Contracts\AuditSourceFactsStore;
use App\Application\Auditor\Contracts\AuditStepUp;
use App\Application\Business\Contracts\BusinessApplicationStore;
use App\Application\Business\Contracts\BusinessAuthorityStore;
use App\Application\Business\Contracts\BusinessCreditFactsStore;
use App\Application\Business\Contracts\BusinessExposureStore;
use App\Application\Environment\Contracts\DemoFixtureStore;
use App\Application\Environment\EnvironmentIsolation;
use App\Application\Evidence\Contracts\StatementExtractionQueue;
use App\Application\Evidence\Contracts\StatementStore;
use App\Application\Evidence\Contracts\StatementTextExtractor;
use App\Application\Identity\Contracts\Authenticator;
use App\Application\Identity\Contracts\ConsentCatalog;
use App\Application\Identity\Contracts\IdentityAccessStore;
use App\Application\Identity\Contracts\IdentityRepository;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Operations\Contracts\OperationJournal;
use App\Application\Pulse\Contracts\PulseSignupRepository;
use App\Infrastructure\Auditor\EloquentAuditAssignmentStore;
use App\Infrastructure\Auditor\EloquentAuditEngagementStore;
use App\Infrastructure\Auditor\EloquentAuditLedgerEvidence;
use App\Infrastructure\Auditor\EloquentAuditLedgerExtractionQueue;
use App\Infrastructure\Auditor\EloquentAuditLocationStore;
use App\Infrastructure\Auditor\EloquentAuditorIndependenceStore;
use App\Infrastructure\Auditor\EloquentAuditorProfileStore;
use App\Infrastructure\Auditor\EloquentAuditReportLifecycle;
use App\Infrastructure\Auditor\EloquentAuditReportPublicationStore;
use App\Infrastructure\Auditor\EloquentAuditReportStore;
use App\Infrastructure\Auditor\EloquentAuditSourceFactsStore;
use App\Infrastructure\Auditor\EloquentAuditStepUp;
use App\Infrastructure\Auditor\JoseAuditReportCryptography;
use App\Infrastructure\Business\EloquentBusinessApplicationStore;
use App\Infrastructure\Business\EloquentBusinessAuthorityStore;
use App\Infrastructure\Business\EloquentBusinessCreditFactsStore;
use App\Infrastructure\Business\EloquentBusinessExposureReservations;
use App\Infrastructure\Environment\EloquentDemoFixtureStore;
use App\Infrastructure\Evidence\EloquentStatementExtractionQueue;
use App\Infrastructure\Evidence\EloquentStatementStore;
use App\Infrastructure\Evidence\IsolatedStatementTextExtractor;
use App\Infrastructure\Identity\EloquentConsentCatalog;
use App\Infrastructure\Identity\EloquentIdentityAccessStore;
use App\Infrastructure\Identity\EloquentIdentityRepository;
use App\Infrastructure\Identity\FortifyAuthenticator;
use App\Infrastructure\Operations\EloquentOperationJournal;
use App\Infrastructure\Operations\JcsCanonicalJson;
use App\Infrastructure\Pulse\EloquentPulseSignupRepository;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Console\Seeds\SeedCommand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Head\Enums\ImageType;
use Laravel\Head\Enums\Media;
use Laravel\Head\Enums\OgType;
use Laravel\Head\Enums\RobotsRule;
use Laravel\Head\Enums\TwitterCard;
use Laravel\Head\Facades\Head;
use Laravel\Head\HeadBuilder;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AuditReportPublicationStore::class, EloquentAuditReportPublicationStore::class);
        $this->app->bind(Authenticator::class, FortifyAuthenticator::class);
        $this->app->bind(AuditStepUp::class, EloquentAuditStepUp::class);
        $this->app->bind(AuditReportCryptography::class, JoseAuditReportCryptography::class);
        $this->app->bind(PulseSignupRepository::class, EloquentPulseSignupRepository::class);
        $this->app->bind(DemoFixtureStore::class, EloquentDemoFixtureStore::class);
        $this->app->bind(IdentityRepository::class, EloquentIdentityRepository::class);
        $this->app->bind(IdentityAccessStore::class, EloquentIdentityAccessStore::class);
        $this->app->bind(CanonicalJson::class, JcsCanonicalJson::class);
        $this->app->bind(OperationJournal::class, EloquentOperationJournal::class);
        $this->app->bind(BusinessAuthorityStore::class, EloquentBusinessAuthorityStore::class);
        $this->app->bind(BusinessCreditFactsStore::class, EloquentBusinessCreditFactsStore::class);
        $this->app->bind(BusinessApplicationStore::class, EloquentBusinessApplicationStore::class);
        $this->app->bind(BusinessExposureStore::class, EloquentBusinessExposureReservations::class);
        $this->app->bind(ConsentCatalog::class, EloquentConsentCatalog::class);
        $this->app->bind(StatementStore::class, EloquentStatementStore::class);
        $this->app->bind(AuditorProfileStore::class, EloquentAuditorProfileStore::class);
        $this->app->bind(AuditAssignmentStore::class, EloquentAuditAssignmentStore::class);
        $this->app->bind(AuditReportStore::class, EloquentAuditReportStore::class);
        $this->app->bind(AuditSourceFactsStore::class, EloquentAuditSourceFactsStore::class);
        $this->app->bind(AuditEngagementStore::class, EloquentAuditEngagementStore::class);
        $this->app->bind(AuditReportLifecycle::class, EloquentAuditReportLifecycle::class);
        $this->app->bind(AuditorIndependenceStore::class, EloquentAuditorIndependenceStore::class);
        $this->app->bind(AuditLocationStore::class, EloquentAuditLocationStore::class);
        $this->app->bind(StatementTextExtractor::class, IsolatedStatementTextExtractor::class);
        $this->app->bind(StatementExtractionQueue::class, EloquentStatementExtractionQueue::class);
        $this->app->bind(AuditLedgerExtractionQueue::class, EloquentAuditLedgerExtractionQueue::class);
        $this->app->bind(AuditLedgerEvidence::class, EloquentAuditLedgerEvidence::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureHead();
        RateLimiter::for('audit-step-up', fn (Request $request): array => [
            Limit::perMinute(5)->by('account:'.$request->user()?->getAuthIdentifier()),
            Limit::perMinute(20)->by('ip:'.$request->ip()),
        ]);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            ! $this->app->make(EnvironmentIsolation::class)->canReset(),
        );

        SeedCommand::prohibit(
            ! $this->app->make(EnvironmentIsolation::class)->canSeed(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Configure the document <head>: favicons, the PWA manifest, and SEO/social meta.
     */
    protected function configureHead(): void
    {
        // Mirrors the headline and standfirst the marketing site actually shows.
        $description = 'Earn up to 18% lending to profitable Rwandan businesses. Buy 3 to 6 month debt notes in audited private businesses, from just RWF 5,000.';

        // Stable browser hints, rendered into the first HTML response and then left untouched.
        Head::inertiaGlobals(fn (HeadBuilder $head) => $head
            ->icon('/favicon.ico', sizes: 'any')
            ->favicon('/favicon.svg', type: ImageType::Svg)
            ->icon('/favicon-96.png', type: ImageType::Png, sizes: '96x96')
            ->appleTouchIcon('/apple-touch-icon.png', sizes: '180x180')
            ->manifest('/site.webmanifest')
            ->themeColor('#ffffff', media: Media::Light)
            ->themeColor('#0a0a0a', media: Media::Dark));

        // SEO + social defaults. The <title> stays with Inertia; og/twitter titles are set explicitly.
        Head::defaults(fn (HeadBuilder $head) => $head
            ->description($description)
            ->meta('author', 'Rozine')
            ->canonical()
            ->og(type: OgType::Website, title: 'Rozine', siteName: 'Rozine', locale: 'en_RW')
            ->ogImage(url('/og-image.png'), alt: 'Rozine — the capital market for everyone else', width: 1200, height: 630, type: ImageType::Png)
            ->twitter(card: TwitterCard::SummaryWithLargeImage, title: 'Rozine')
            ->robots(app()->isProduction()
                ? [RobotsRule::Index, RobotsRule::Follow]
                : [RobotsRule::NoIndex, RobotsRule::NoFollow]));
    }
}

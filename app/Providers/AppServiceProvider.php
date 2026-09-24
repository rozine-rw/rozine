<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Business\Contracts\BusinessAuthorityStore;
use App\Application\Environment\Contracts\DemoFixtureStore;
use App\Application\Environment\EnvironmentIsolation;
use App\Application\Identity\Contracts\ConsentCatalog;
use App\Application\Identity\Contracts\IdentityAccessStore;
use App\Application\Identity\Contracts\IdentityRepository;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Operations\Contracts\OperationJournal;
use App\Application\Pulse\Contracts\PulseSignupRepository;
use App\Infrastructure\Business\EloquentBusinessAuthorityStore;
use App\Infrastructure\Environment\EloquentDemoFixtureStore;
use App\Infrastructure\Identity\EloquentConsentCatalog;
use App\Infrastructure\Identity\EloquentIdentityAccessStore;
use App\Infrastructure\Identity\EloquentIdentityRepository;
use App\Infrastructure\Operations\EloquentOperationJournal;
use App\Infrastructure\Operations\JcsCanonicalJson;
use App\Infrastructure\Pulse\EloquentPulseSignupRepository;
use Carbon\CarbonImmutable;
use Illuminate\Database\Console\Seeds\SeedCommand;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
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
        $this->app->bind(PulseSignupRepository::class, EloquentPulseSignupRepository::class);
        $this->app->bind(DemoFixtureStore::class, EloquentDemoFixtureStore::class);
        $this->app->bind(IdentityRepository::class, EloquentIdentityRepository::class);
        $this->app->bind(IdentityAccessStore::class, EloquentIdentityAccessStore::class);
        $this->app->bind(CanonicalJson::class, JcsCanonicalJson::class);
        $this->app->bind(OperationJournal::class, EloquentOperationJournal::class);
        $this->app->bind(BusinessAuthorityStore::class, EloquentBusinessAuthorityStore::class);
        $this->app->bind(ConsentCatalog::class, EloquentConsentCatalog::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureHead();
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

<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
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
        //
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
            app()->isProduction(),
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
        $description = 'Earn up to 15% by investing in Rwandan businesses from just RWF 5,000.';

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

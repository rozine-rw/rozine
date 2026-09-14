<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Environment\EnvironmentIsolation;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Database\Console\Seeds\SeedCommand;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class EnvironmentSafetyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(EnvironmentIsolation::class);

        $this->app->booting(function (): void {
            $this->app->make(EnvironmentIsolation::class)->configure();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(EnvironmentIsolation $isolation): void
    {
        SeedCommand::prohibit(! $isolation->canSeed());

        Event::listen(CommandStarting::class, function (CommandStarting $event) use ($isolation): void {
            $isolation->guardCommand($event->command);
        });

        if ($isolation->isIsolated()) {
            Http::preventStrayRequests();
        }
    }
}

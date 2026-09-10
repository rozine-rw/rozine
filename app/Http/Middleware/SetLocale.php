<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Negotiates the request locale against the supported set.
 *
 * The resolved locale reaches the browser two ways: the root template renders it into
 * `<html lang>`, which the React provider reads, and `HandleInertiaRequests` shares it as a prop.
 * Both come from `app()->getLocale()`, so there is one authority.
 *
 * No user-level locale preference is stored yet. That belongs with the Party model, and which
 * locales must be complete at launch is still D-07.
 */
class SetLocale
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale($this->resolve($request));

        return $next($request);
    }

    /**
     * Picks the best supported locale for the request, preferring an exact match over the base
     * language so `fr-RW` resolves to `fr` rather than falling through to the default.
     */
    private function resolve(Request $request): string
    {
        /** @var list<string> $supported */
        $supported = config('i18n.supported');

        foreach ($this->preferences($request) as $preference) {
            if (in_array($preference, $supported, true)) {
                return $preference;
            }

            $base = explode('-', $preference)[0];

            if (in_array($base, $supported, true)) {
                return $base;
            }
        }

        return config('app.locale');
    }

    /**
     * @return list<string>
     */
    private function preferences(Request $request): array
    {
        return array_values(array_map(
            static fn (string $language): string => str_replace('_', '-', $language),
            $request->getLanguages(),
        ));
    }
}

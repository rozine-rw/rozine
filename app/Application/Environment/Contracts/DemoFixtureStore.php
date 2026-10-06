<?php

declare(strict_types=1);

namespace App\Application\Environment\Contracts;

interface DemoFixtureStore
{
    /**
     * @param  non-empty-list<array<string, string|int|bool|null>>  $fixtures
     */
    public function replace(array $fixtures, string $provenance): int;
}

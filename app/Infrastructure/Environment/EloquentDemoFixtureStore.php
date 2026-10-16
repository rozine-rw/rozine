<?php

declare(strict_types=1);

namespace App\Infrastructure\Environment;

use App\Application\Environment\Contracts\DemoFixtureStore;
use App\Application\Environment\EnvironmentIsolation;
use App\Models\PulseSignup;
use Illuminate\Database\DatabaseManager;
use LogicException;

class EloquentDemoFixtureStore implements DemoFixtureStore
{
    public function __construct(
        private readonly EnvironmentIsolation $isolation,
        private readonly DatabaseManager $database,
    ) {}

    /**
     * @param  non-empty-list<array<string, string|int|bool|null>>  $fixtures
     */
    public function replace(array $fixtures, string $provenance): int
    {
        $this->isolation->assertDemoResetAllowed();
        $this->isolation->configure();

        $connectionName = $this->database->getDefaultConnection();
        $this->database->purge($connectionName);
        $connection = $this->database->connection($connectionName);
        $table = (new PulseSignup)->getTable();

        return $connection->transaction(function () use ($connection, $fixtures, $table, $provenance): int {
            foreach ($fixtures as $fixture) {
                $connection->table($table)->insertOrIgnore($fixture);
                $updated = $connection->table($table)
                    ->where('contact', $fixture['contact'])
                    ->where('user_agent', $provenance)
                    ->update($fixture);

                if ($updated !== 1) {
                    throw new LogicException('DEMO_FIXTURE_PROVENANCE_COLLISION');
                }
            }

            return count($fixtures);
        });
    }
}

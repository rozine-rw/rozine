<?php

declare(strict_types=1);

use App\Application\Environment\EnvironmentIsolation;
use Database\Seeders\SyntheticWalletSeeder;
use Illuminate\Support\Facades\ParallelTesting;
use Tests\TestCase;

uses(TestCase::class);

it('checks isolation through the seeder entry point before doing any work', function (): void {
    ParallelTesting::resolveTokenUsing(fn (): false => false);
    expect(fn () => app(SyntheticWalletSeeder::class)->run())->not->toThrow(Exception::class);

    config(['database.connections.pgsql.database' => 'rozine_test_test_1']);
    expect(fn () => app(SyntheticWalletSeeder::class)->run())->toThrow(LogicException::class, 'WALLET_LOCAL_DATABASE_REQUIRED');
});

it('admits only the serial testing database or the exact numeric parallel worker database', function (string $database, string|false $token): void {
    config(['database.connections.pgsql.database' => $database]);
    ParallelTesting::resolveTokenUsing(fn (): string|false => $token);

    expect(fn () => app(SyntheticWalletSeeder::class)->assertLocal())->not->toThrow(Exception::class);
})->with([
    'serial without token' => ['rozine_test', false],
    'serial with token' => ['rozine_test', '1'],
    'first worker' => ['rozine_test_test_1', '1'],
    'multiple-digit worker' => ['rozine_test_test_12', '12'],
]);

it('refuses a worker database without its exact canonical numeric token', function (string $database, string|false $token): void {
    config(['database.connections.pgsql.database' => $database]);
    ParallelTesting::resolveTokenUsing(fn (): string|false => $token);

    expect(fn () => app(SyntheticWalletSeeder::class)->assertLocal())->toThrow(LogicException::class, 'WALLET_LOCAL_DATABASE_REQUIRED');
})->with([
    'no token' => ['rozine_test_test_1', false],
    'empty token' => ['rozine_test_test_1', ''],
    'another worker' => ['rozine_test_test_2', '1'],
    'zero token' => ['rozine_test_test_0', '0'],
    'leading zero' => ['rozine_test_test_01', '01'],
    'negative token' => ['rozine_test_test_-1', '-1'],
    'text token' => ['rozine_test_test_worker', 'worker'],
    'newline token' => ["rozine_test_test_1\n", "1\n"],
    'extra suffix' => ['rozine_test_test_1_extra', '1'],
    'missing worker suffix' => ['rozine_test_1', '1'],
    'local database on testing' => ['rozine', '1'],
    'manual database on testing' => ['rozine_manual', '1'],
]);

it('preserves connection refusals even with a matching worker token', function (array $overrides): void {
    config(['database.connections.pgsql.database' => 'rozine_test_test_1']);
    config(['database.connections.pgsql' => [...config()->array('database.connections.pgsql'), ...$overrides]]);
    ParallelTesting::resolveTokenUsing(fn (): string => '1');

    expect(fn () => app(SyntheticWalletSeeder::class)->assertLocal())->toThrow(LogicException::class, 'WALLET_LOCAL_DATABASE_REQUIRED');
})->with([
    'remote host' => [['host' => 'db.example.test']],
    'another driver' => [['driver' => 'sqlite']],
    'connection URL' => [['url' => 'pgsql://localhost/rozine_test_test_1']],
    'read connection' => [['read' => []]],
    'write connection' => [['write' => []]],
]);

it('preserves the local database allowlist and refuses parallel databases on local', function (string $database, bool $allowed): void {
    config(['database.connections.pgsql.database' => $database]);
    ParallelTesting::resolveTokenUsing(fn (): string => '1');
    $isolation = Mockery::mock(EnvironmentIsolation::class, [app(), config()], ['profile' => 'local'])->makePartial();
    app()->instance(EnvironmentIsolation::class, $isolation);

    $assertLocal = fn () => app(SyntheticWalletSeeder::class)->assertLocal();
    if ($allowed) {
        expect($assertLocal)->not->toThrow(Exception::class);
    } else {
        expect($assertLocal)->toThrow(LogicException::class, 'WALLET_LOCAL_DATABASE_REQUIRED');
    }
})->with([
    'local' => ['rozine', true],
    'serial tests' => ['rozine_test', true],
    'manual' => ['rozine_manual', true],
    'worker' => ['rozine_test_test_1', false],
]);

it('does not make a parallel token bypass seeding or synthetic environment gates', function (string $profile, string $code): void {
    config(['database.connections.pgsql.database' => 'rozine_test_test_1']);
    ParallelTesting::resolveTokenUsing(fn (): string => '1');
    $isolation = Mockery::mock(EnvironmentIsolation::class, [app(), config()], ['profile' => $profile, 'assertSafeConfiguration' => null])->makePartial();
    app()->instance(EnvironmentIsolation::class, $isolation);

    expect(fn () => app(SyntheticWalletSeeder::class)->assertLocal())->toThrow(LogicException::class, $code);
})->with([
    'UAT' => ['uat', 'ISOLATION_SEED_DENIED'],
    'demo' => ['demo', 'WALLET_SYNTHETIC_ONLY'],
    'production' => ['production', 'WALLET_SYNTHETIC_ONLY'],
]);

it('refuses live money even on an exact parallel worker database', function (): void {
    config(['database.connections.pgsql.database' => 'rozine_test_test_1', 'isolation.live_money_enabled' => true]);
    ParallelTesting::resolveTokenUsing(fn (): string => '1');

    expect(fn () => app(SyntheticWalletSeeder::class)->assertLocal())->toThrow(LogicException::class, 'WALLET_SYNTHETIC_ONLY');
});

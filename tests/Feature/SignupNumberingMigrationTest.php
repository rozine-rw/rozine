<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/*
 * The numbering migration runs once, against live waitlist rows that were
 * numbered by the racy `count() + 1` allocation, so its data path deserves a
 * test rather than a first run in production. Each case rewinds the migration,
 * plants the state it has to cope with, and runs it forward again.
 */

/**
 * The shape a migration file actually returns.
 *
 * Migrations are anonymous classes, and Illuminate's Migration base class
 * declares neither up() nor down() - the migrator reaches them reflectively.
 * Naming the shape here lets the calls below be type-checked instead of being
 * silently unverifiable. It is a documentation type only: the migration does
 * not implement it, so the accessor returns object at runtime.
 */
interface NumberingMigration
{
    public function up(): void;

    public function down(): void;
}

/**
 * @return NumberingMigration
 */
function numberingMigration(): object
{
    /** @var NumberingMigration $migration */
    $migration = require database_path('migrations/2026_09_06_121310_serialize_pulse_signup_numbering.php');

    return $migration;
}

/**
 * @param  array<string, mixed>  $attributes
 */
function plantSignup(array $attributes): int
{
    return DB::table('pulse_signups')->insertGetId([
        'type' => 'business',
        'name' => 'Planted',
        'contact_method' => 'email',
        'contact' => fake()->unique()->safeEmail(),
        'province' => 'Kigali City',
        'district' => 'Gasabo',
        'created_at' => now(),
        'updated_at' => now(),
        ...$attributes,
    ]);
}

it('reissues only the duplicate holders and leaves correct numbers alone', function () {
    numberingMigration()->down();

    $first = plantSignup(['queue_number' => '#0001', 'loan_number' => '#1,001']);
    $second = plantSignup(['queue_number' => '#0002', 'loan_number' => '#1,002']);
    $collided = plantSignup(['queue_number' => '#0002', 'loan_number' => '#1,002']);

    numberingMigration()->up();

    $numbers = DB::table('pulse_signups')->pluck('queue_number', 'id');

    expect($numbers[$first])->toBe('#0001')
        ->and($numbers[$second])->toBe('#0002')
        ->and($numbers[$collided])->not->toBe('#0002')
        ->and(DB::table('pulse_signups')->pluck('queue_number')->unique())->toHaveCount(3)
        ->and(DB::table('pulse_signups')->pluck('loan_number')->unique())->toHaveCount(3);
});

it('seeds each counter from the highest number already issued, not from the row count', function () {
    numberingMigration()->down();

    // A gap: rows were deleted, so counting them would reissue #0007.
    plantSignup(['type' => 'investor', 'queue_number' => '#0007']);
    plantSignup(['queue_number' => '#0031', 'loan_number' => '#1,412']);

    numberingMigration()->up();

    $counters = DB::table('signup_counters')->pluck('value', 'name');

    expect((int) $counters['queue_number:investor'])->toBe(7)
        ->and((int) $counters['queue_number:business'])->toBe(31)
        ->and((int) $counters['loan_number'])->toBe(1412);
});

it('leaves the numbering unique once it has run', function () {
    numberingMigration()->down();

    plantSignup(['queue_number' => '#0001']);

    numberingMigration()->up();

    expect(fn () => plantSignup(['queue_number' => '#0001']))->toThrow(QueryException::class);
});

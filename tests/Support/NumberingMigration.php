<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * The shape a migration file returns.
 *
 * Migrations are anonymous classes, and Illuminate's Migration base class declares neither up() nor
 * down() — the migrator reaches them reflectively. Naming the shape lets a test that drives a
 * migration directly be type-checked instead of being silently unverifiable.
 *
 * It is a documentation type. No migration implements it, so an accessor returning one declares
 * `object` at runtime and narrows to this in its docblock.
 *
 * It lives here rather than beside the test that uses it because `tests/Support` is outside every
 * suite in phpunit.xml, so Pest never scans it, and because a type declared inside a test file whose
 * name does not match it breaks PSR-4 and makes Composer warn on every autoload dump.
 */
interface NumberingMigration
{
    public function up(): void;

    public function down(): void;
}

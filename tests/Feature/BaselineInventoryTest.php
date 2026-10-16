<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

/*
 * The Phase 0 baseline inventory.
 *
 * The document is only worth having if it still describes the code, so the
 * drift check is the part that matters most here: it is what turns a snapshot
 * that rots into a record that stays true.
 */

function inventoryPath(): string
{
    return base_path('docs/phase-0/baseline-inventory.md');
}

beforeEach(function () {
    $this->original = File::exists(inventoryPath()) ? File::get(inventoryPath()) : null;
});

afterEach(function () {
    if ($this->original === null) {
        File::delete(inventoryPath());

        return;
    }

    File::put(inventoryPath(), $this->original);
});

it('captures every section Phase 0 asks for', function () {
    expect(Artisan::call('inventory:baseline'))->toBe(0);

    $inventory = File::get(inventoryPath());

    expect($inventory)
        ->toContain('# Phase 0 baseline inventory')
        ->toContain('## Runtime contract')
        ->toContain('## Schema')
        ->toContain('## Migrations')
        ->toContain('## Routes')
        ->toContain('## Authentication')
        ->toContain('## Module boundaries')
        ->toContain('## CI gates')
        ->toContain('## Deployment targets');
});

it('records the schema, routes and layering as they actually are', function () {
    expect(Artisan::call('inventory:baseline'))->toBe(0);

    $inventory = File::get(inventoryPath());

    expect($inventory)
        // A table with its columns and a unique index, read from the connection.
        ->toContain('### `pulse_signups`')
        ->toContain('`queue_number`')
        // A first-party route with its middleware, and no vendor route.
        ->toContain('`PulseController@index`')
        ->toContain('throttle:10,1')
        ->not->toContain('Laravel\\Fortify\\Http\\Controllers')
        // The ADR-0001 layers.
        ->toContain('`app/Domain`')
        ->toContain('`app/Application`')
        ->toContain('`app/Infrastructure`')
        // Gates and deployment targets.
        ->toContain('PostgreSQL concurrency lane')
        ->toContain('/var/www/rozine-prod')
        ->toContain('Isolation profile')
        ->toContain('`/var/www/rozine-prod` | `production`')
        ->toContain('`/var/www/rozine` | `uat`');
});

it('names deployment secrets without ever recording their values', function () {
    expect(Artisan::call('inventory:baseline'))->toBe(0);

    $inventory = File::get(inventoryPath());

    expect($inventory)->toContain('STAGING_SSH_KEY')
        ->and($inventory)->not->toContain('BEGIN OPENSSH PRIVATE KEY');
});

it('passes its own drift check immediately after generating', function () {
    expect(Artisan::call('inventory:baseline'))->toBe(0);
    expect(Artisan::call('inventory:baseline', ['--check' => true]))->toBe(0);
});

it('fails the drift check when the committed inventory no longer matches the code', function () {
    expect(Artisan::call('inventory:baseline'))->toBe(0);

    File::put(inventoryPath(), File::get(inventoryPath())."\nA column nobody added.\n");

    expect(Artisan::call('inventory:baseline', ['--check' => true]))->toBe(1);
});

it('fails the drift check when the inventory is missing entirely', function () {
    File::delete(inventoryPath());

    expect(Artisan::call('inventory:baseline', ['--check' => true]))->toBe(1);
});

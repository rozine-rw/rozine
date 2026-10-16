<?php

declare(strict_types=1);

use PhpParser\Error;
use PhpParser\Node\DeclareItem;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Stmt\Declare_;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use Symfony\Component\Finder\Finder;

$declaresStrictTypes = static function (string $source): bool {
    try {
        $statements = (new ParserFactory)->createForHostVersion()->parse($source) ?? [];
    } catch (Error) {
        return false;
    }

    $firstStatement = $statements[0] ?? null;

    if (! $firstStatement instanceof Declare_ || $firstStatement->stmts !== null) {
        return false;
    }

    $declarations = (new NodeFinder)->findInstanceOf($statements, DeclareItem::class);
    $strictDeclarations = array_values(array_filter(
        $declarations,
        static fn (DeclareItem $declaration): bool => $declaration->key->name === 'strict_types',
    ));

    return count($strictDeclarations) === 1
        && in_array($strictDeclarations[0], $firstStatement->declares, true)
        && $strictDeclarations[0]->value instanceof Int_
        && $strictDeclarations[0]->value->value === 1;
};

arch('application symbols declare strict types')
    ->expect('App')
    ->toUseStrictTypes();

it('requires strict types in every first-party PHP file', function (string $directory) use ($declaresStrictTypes): void {
    $root = dirname(__DIR__, 2);
    $files = Finder::create()
        ->files()
        ->ignoreDotFiles(false)
        ->in($root.'/'.$directory)
        ->name('/\.php$/')
        ->notName('/\.blade\.php$/')
        ->filter(static fn (SplFileInfo $file): bool => ! str_starts_with($file->getPathname(), $root.'/bootstrap/cache/'))
        ->sortByName();

    expect($files->count())->toBeGreaterThan(0, 'The approved directory must not disappear from the strict-types scope.');

    foreach ($files as $file) {
        expect($declaresStrictTypes($file->getContents()))
            ->toBeTrue($file->getPathname().' must start with declare(strict_types=1); and must not redeclare it.');
    }
})->with(['app', 'bootstrap', 'config', 'database', 'routes', 'tests'])->group('arch');

it('validates the executable declaration rather than matching comments or strings', function (string $source, bool $expected) use ($declaresStrictTypes): void {
    expect($declaresStrictTypes($source))->toBe($expected);
})->with([
    'enabled' => ['<?php declare(strict_types=1); return [];', true],
    'header comment and whitespace' => ["<?php\n/* License */\n// Header\ndeclare ( strict_types = 1 );\nreturn [];", true],
    'hash comment' => ["<?php\n# Header\ndeclare(strict_types=1);", true],
    'anonymous migration' => ['<?php declare(strict_types=1); return new class {};', true],
    'no declaration' => ['<?php return [];', false],
    'disabled' => ['<?php declare(strict_types=0); return [];', false],
    'comment only' => ['<?php /* declare(strict_types=1); */ return [];', false],
    'string only' => ['<?php $example = "declare(strict_types=1);";', false],
    'leading output' => ['output<?php declare(strict_types=1);', false],
    'late declaration' => ['<?php $value = 1; declare(strict_types=1);', false],
    'later disabling' => ['<?php declare(strict_types=1); declare(strict_types=0);', false],
    'scoped declaration' => ['<?php declare(strict_types=1) {}', false],
    'string value' => ['<?php declare(strict_types="1");', false],
    'different directive first' => ['<?php declare(ticks=1); declare(strict_types=1);', false],
    'invalid PHP' => ['<?php declare(strict_types=1); function {', false],
    'empty source' => ['', false],
])->group('arch');

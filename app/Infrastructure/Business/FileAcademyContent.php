<?php

declare(strict_types=1);

namespace App\Infrastructure\Business;

use App\Application\Business\Contracts\AcademyContent;

/**
 * The Business Academy's modules as written in `resources/academy/business.php`.
 *
 * @phpstan-import-type Module from \App\Domain\Business\ReadinessAcademy
 */
final class FileAcademyContent implements AcademyContent
{
    public function modules(): array
    {
        /** @var array<string, Module> $modules */
        $modules = require resource_path('academy/business.php');

        return $modules;
    }
}

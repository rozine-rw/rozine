<?php

declare(strict_types=1);

namespace App\Application\Business\Contracts;

/**
 * The Business Academy's modules (BRS v3.0 RDY-R07), keyed by module, in syllabus order.
 *
 * @phpstan-import-type Module from \App\Domain\Business\ReadinessAcademy
 */
interface AcademyContent
{
    /** @return array<string, Module> */
    public function modules(): array;
}

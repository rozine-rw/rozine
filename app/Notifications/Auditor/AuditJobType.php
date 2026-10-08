<?php

declare(strict_types=1);

namespace App\Notifications\Auditor;

/** The kind of job dispatched to an Audit Partner (FR-302). */
enum AuditJobType: string
{
    case Routine = 'routine';
    case Flash = 'flash';
}

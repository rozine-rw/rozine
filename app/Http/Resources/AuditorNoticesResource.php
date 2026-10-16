<?php

declare(strict_types=1);

namespace App\Http\Resources;

/** The Audit Partner's own notices for the Auditor bell, marked read through the Auditor app's routes. */
class AuditorNoticesResource extends InvestorNoticesResource
{
    protected const string NOTICES = 'auditor.notices';
}

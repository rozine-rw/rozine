<?php

declare(strict_types=1);

namespace App\Http\Resources;

/** The Audit Partner's own notices for the Auditor bell, marked read through the Auditor app's routes. */
class AuditorNoticesResource extends InvestorNoticesResource
{
    protected const string NOTICES = 'auditor.notices';

    /** Security and support notices open the Audit Partner's own profile, never the Investor app's. */
    protected const array OWN_DESTINATIONS = [
        'security.password_changed' => ['auditor.profile', []],
        'security.new_device' => ['auditor.profile', []],
        'security.payout_account_linked' => ['auditor.profile', []],
        'security.payout_account_unlinked' => ['auditor.profile', []],
        'support.complaint_answered' => ['auditor.profile', []],
        'support.complaint_resolved' => ['auditor.profile', []],
    ];
}

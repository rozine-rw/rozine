<?php

declare(strict_types=1);

namespace App\Application\Auditor\Contracts;

/**
 * Who verified a note (BRS v2.0 FEE-R02): the author of the latest sealed audit report on the
 * application a campaign was raised from, whose share of the platform fee is credited at disbursement.
 */
interface NoteAuditPartner
{
    /** @return array{report_id: string, party_id: string}|null null when no sealed report exists for the campaign's application */
    public function forCampaign(string $campaignId): ?array;
}

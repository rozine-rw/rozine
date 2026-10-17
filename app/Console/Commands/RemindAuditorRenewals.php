<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Auditor\RemindAccreditationRenewals;
use Illuminate\Console\Command;

class RemindAuditorRenewals extends Command
{
    /** @var string */
    protected $signature = 'auditors:remind-renewals';

    /** @var string */
    protected $description = 'Remind Audit Partners whose licence lapses within 45 days to upload the renewal, once per expiry';

    public function handle(RemindAccreditationRenewals $renewals): int
    {
        $this->info('Reminded '.$renewals->handle().' Audit Partners to renew.');

        return self::SUCCESS;
    }
}

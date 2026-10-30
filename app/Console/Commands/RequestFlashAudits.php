<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Auditor\RequestFlashAudits as FlashAudits;
use Illuminate\Console\Command;

class RequestFlashAudits extends Command
{
    /** @var string */
    protected $signature = 'audits:request-flash';

    /** @var string */
    protected $description = 'Draw whether to send an unannounced Flash audit to a funded business picked at random';

    public function handle(FlashAudits $audits): int
    {
        $this->info('Requested '.$audits->handle(now()->toDateTimeImmutable()).' Flash audits.');

        return self::SUCCESS;
    }
}

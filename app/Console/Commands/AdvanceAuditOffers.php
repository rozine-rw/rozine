<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Auditor\AdvanceExpiredAuditOffers;
use Illuminate\Console\Command;

class AdvanceAuditOffers extends Command
{
    /** @var string */
    protected $signature = 'audits:advance-offers {--limit=100 : Maximum expired offers to advance (1-1000)}';

    /** @var string */
    protected $description = 'Reoffer expired audit jobs under current eligibility and original deadlines';

    public function handle(AdvanceExpiredAuditOffers $offers): int
    {
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000]]);
        if ($limit === false) {
            $this->error('The limit must be an integer from 1 to 1000.');

            return self::INVALID;
        }
        $this->info('Advanced '.$offers->handle($limit).' expired audit offers.');

        return self::SUCCESS;
    }
}

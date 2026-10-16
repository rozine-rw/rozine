<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Business\ManageBusinessCampaigns;
use Illuminate\Console\Command;

class ExpireBusinessCampaigns extends Command
{
    protected $signature = 'campaigns:expire {--limit=100 : Maximum due campaigns to close (1-1000)}';

    protected $description = 'Close expired unfunded campaigns and release their retained exposure';

    public function handle(ManageBusinessCampaigns $campaigns): int
    {
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000]]);
        if ($limit === false) {
            $this->error('The limit must be an integer from 1 to 1000.');

            return self::INVALID;
        }
        $this->info('Expired '.$campaigns->expireDue($limit).' campaigns.');

        return self::SUCCESS;
    }
}

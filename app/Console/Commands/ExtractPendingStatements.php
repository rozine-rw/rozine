<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Evidence\Contracts\StatementExtractionQueue;
use Illuminate\Console\Command;

class ExtractPendingStatements extends Command
{
    protected $signature = 'statements:extract {--limit=5 : Maximum committed originals to process, from 1 to 20}';

    protected $description = 'Extract pending private statement text in bounded processes without Business or journal locks';

    public function handle(StatementExtractionQueue $queue): int
    {
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 20]]);
        if ($limit === false) {
            $this->components->error('Choose a batch size from 1 to 20.');

            return self::FAILURE;
        }
        $this->components->info('Processed '.$queue->processPending($limit).' pending statement originals.');

        return self::SUCCESS;
    }
}

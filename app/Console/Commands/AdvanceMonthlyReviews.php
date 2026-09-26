<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Auditor\ReviewAuditPublication;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class AdvanceMonthlyReviews extends Command
{
    protected $signature = 'audits:advance-reviews {--limit=100 : Maximum due reviews to inspect (1-1000)}';

    protected $description = 'Publish eligible undisputed monthly reports after their retained review window';

    public function handle(ReviewAuditPublication $reviews): int
    {
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000]]);
        if ($limit === false) {
            $this->error('The limit must be an integer from 1 to 1000.');

            return self::INVALID;
        }
        $cursor = Cache::get('monthly-audit-review-sweep-cursor');
        $cursor = is_string($cursor) ? $cursor : null;
        $published = 0;
        $inspected = 0;
        do {
            $result = $reviews->advanceDue(min(100, $limit - $inspected), $cursor);
            $inspected += $result['inspected'];
            $published += $result['published'];
            foreach ($result['blocked'] as $id => $code) {
                $this->warn('Review '.$id.' remains unpublished: '.$code);
            }
            $cursor = $result['next_cursor'];
            Cache::put('monthly-audit-review-sweep-cursor', $cursor);
        } while ($cursor !== null && $inspected < $limit);
        $this->info('Inspected '.$inspected.' due reviews; published '.$published.'.');

        return self::SUCCESS;
    }
}

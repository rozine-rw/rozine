<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Primary\Contracts\CartCheckouts;
use Illuminate\Console\Command;

class ExpireCartCheckouts extends Command
{
    protected $signature = 'cart:expire-checkouts {--limit=100 : Maximum overdue cart checkouts to examine (1-1000)}';

    protected $description = 'Expire overdue whole-cart checkouts, every line together, and return their held principal';

    public function handle(CartCheckouts $checkouts): int
    {
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000]]);
        if ($limit === false) {
            $this->error('The limit must be an integer from 1 to 1000.');

            return self::INVALID;
        }
        $this->info('Expired '.$checkouts->expireDue($limit).' cart checkouts.');

        return self::SUCCESS;
    }
}

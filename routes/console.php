<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('audits:advance-offers')->everyMinute()->withoutOverlapping(5);

Schedule::command('statements:extract')->everyMinute()->withoutOverlapping(5);

Schedule::command('audits:advance-reviews')->everyMinute()->withoutOverlapping(5);

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('campaigns:expire')->everyMinute()->withoutOverlapping(5);

Schedule::command('primary:expire-reservations')->everyMinute()->withoutOverlapping(5);

Schedule::command('disbursements:reconcile')->everyMinute()->withoutOverlapping(5);

Schedule::command('changes:prune')->daily()->withoutOverlapping(60);

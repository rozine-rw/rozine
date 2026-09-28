<?php

declare(strict_types=1);
use PHPUnit\Event\Facade;
use PHPUnit\Event\Test\Finished;
use PHPUnit\Event\Test\FinishedSubscriber;

require_once dirname(__DIR__, 2).'/vendor/autoload.php';

$destination = getenv('PHP_SHARD_TEST_IDS');
if (! is_string($destination) || $destination === '') {
    throw new RuntimeException('Missing shard execution evidence destination.');
}
file_put_contents($destination, '');

Facade::instance()->registerSubscriber(new class($destination) implements FinishedSubscriber
{
    public function __construct(private readonly string $destination) {}

    public function notify(Finished $event): void
    {
        if (file_put_contents($this->destination, json_encode($event->test()->id(), JSON_THROW_ON_ERROR)."\n", FILE_APPEND | LOCK_EX) === false) {
            throw new RuntimeException('Cannot record an executed test.');
        }
    }
});

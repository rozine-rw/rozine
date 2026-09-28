<?php

declare(strict_types=1);

use PHPUnit\Event\Facade;
use PHPUnit\Event\Test\Passed;
use PHPUnit\Event\Test\PassedSubscriber;
use PHPUnit\Event\TestSuite\Loaded;
use PHPUnit\Event\TestSuite\LoadedSubscriber;

require_once dirname(__DIR__, 2).'/vendor/autoload.php';

$destination = getenv('PHP_SHARD_TEST_IDS');
$catalog = getenv('PHP_SHARD_CATALOG');
if (! $destination && ! $catalog) {
    throw new RuntimeException('Missing shard evidence destination.');
}

if ($destination) {
    file_put_contents($destination, '');
    Facade::instance()->registerSubscriber(new class($destination) implements PassedSubscriber
    {
        public function __construct(private readonly string $destination) {}

        public function notify(Passed $event): void
        {
            // Dataset labels may contain arbitrary bytes; preserve them without lossy UTF-8 conversion.
            $record = ['id' => base64_encode($event->test()->id()), 'status' => 'passed'];
            if (file_put_contents($this->destination, json_encode($record, JSON_THROW_ON_ERROR)."\n", FILE_APPEND | LOCK_EX) === false) {
                throw new RuntimeException('Cannot record a passed test.');
            }
        }
    });
}

if ($catalog) {
    Facade::instance()->registerSubscriber(new class($catalog) implements LoadedSubscriber
    {
        public function __construct(private readonly string $destination) {}

        public function notify(Loaded $event): void
        {
            $ids = [];
            foreach ($event->testSuite()->tests() as $test) {
                $ids[] = base64_encode($test->id());
            }
            if (file_put_contents($this->destination, json_encode($ids, JSON_THROW_ON_ERROR)) === false) {
                throw new RuntimeException('Cannot record the complete test catalog.');
            }
        }
    });
}

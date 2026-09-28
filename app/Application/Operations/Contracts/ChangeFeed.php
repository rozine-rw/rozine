<?php

declare(strict_types=1);

namespace App\Application\Operations\Contracts;

use App\Domain\Operations\ChangeCursor;
use App\Domain\Operations\ChangeScope;

/**
 * The append-only "what changed" feed behind the online propagation beacon (S4-E). It never says
 * what a record now is: a reader reloads the facts through its own authorized page.
 *
 * @phpstan-type Audience array{scope: ChangeScope, topic: string}
 * @phpstan-type Change array{topic: string, subject: string, revision: int}
 */
interface ChangeFeed
{
    /**
     * Records a change inside the caller's open transaction, so it commits or rolls back with the
     * effect itself. A null revision takes the subject's next revision, serialized per subject.
     */
    public function record(ChangeScope $scope, string $topic, string $subject, ?int $revision = null): void;

    /** The snapshot horizon a fresh cursor starts from: every change below it is already visible. */
    public function horizon(): int;

    /**
     * The changes committed since `$after` for the given audiences only: the latest revision per
     * subject, leaving out any revision not newer than one already delivered.
     *
     * @param  list<Audience>  $audiences
     * @return array{changes: list<Change>, xmin: int, after_id: int, overflow: bool}
     */
    public function read(array $audiences, ChangeCursor $after, int $limit): array;

    /** Deletes changes older than the retention, keeping each subject's latest revision. */
    public function prune(int $retentionHours): int;
}

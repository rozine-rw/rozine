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
     * Revisions are observation sequences, never a record's own version: a topic with more than
     * one emitter for a subject (a campaign's progress and its closure) must leave them all null.
     */
    public function record(ChangeScope $scope, string $topic, string $subject, ?int $revision = null): void;

    /**
     * Where a fresh cursor starts: the snapshot horizon, below which every change is already
     * visible, and the highest feed id visible now.
     *
     * @return array{xmin: int, after_id: int}
     */
    public function horizon(): array;

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

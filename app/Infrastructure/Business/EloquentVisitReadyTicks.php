<?php

declare(strict_types=1);

namespace App\Infrastructure\Business;

use App\Application\Business\Contracts\VisitReadyTicks;
use App\Application\Business\WithBusinessAuthority;
use App\Application\Operations\Contracts\OperationJournal;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Models\BusinessVisitReadyTick;
use Illuminate\Support\Facades\DB;

/**
 * The visit-ready tick adapter. A tick is journaled under the acting Party and targets the Business
 * profile; each one appends a row, and an item's latest row is its current state. Rows of one
 * checklist are appended under an advisory lock so their order is the order they were saved in.
 */
final class EloquentVisitReadyTicks implements VisitReadyTicks
{
    public function __construct(private WithBusinessAuthority $authority, private OperationJournal $journal) {}

    public function ticked(string $businessId, string $dueOn): array
    {
        $latest = [];
        foreach (BusinessVisitReadyTick::query()->where('business_id', $businessId)->whereDate('due_on', $dueOn)->orderBy('created_at')->orderBy('id')->get() as $row) {
            $latest[$row->item] = $row->ticked;
        }

        return array_keys(array_filter($latest));
    }

    public function tick(int $userId, int $contextRevision, string $businessId, string $requestId, string $dueOn, string $item, bool $ticked): array
    {
        return $this->authority->handle($userId, $contextRevision, $businessId, 'business.view', null,
            function (array $business, array $identity) use ($userId, $contextRevision, $businessId, $requestId, $dueOn, $item, $ticked): array {
                $partyId = (string) ($identity['party']['id'] ?? throw new CommandRejection('BUSINESS_NOT_FOUND', 404));
                $permitted = ['identity_context_revision' => $contextRevision, 'due_on' => $dueOn, 'item' => $item, 'ticked' => $ticked];

                return $this->journal->execute('party:'.$partyId, $userId, 'business.visit_ready.tick', $requestId, 'business_profile', $business['id'], $permitted,
                    function () use ($userId, $contextRevision, $businessId): void {
                        $this->authority->handle($userId, $contextRevision, $businessId, 'business.view', null, fn (): bool => true);
                    },
                    function (string $operationId) use ($business, $partyId, $dueOn, $item, $ticked): OperationResult {
                        DB::select('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))', ['business-visit-ready|'.$business['id'].'|'.$dueOn]);
                        $row = new BusinessVisitReadyTick;
                        $row->forceFill(['business_id' => $business['id'], 'due_on' => $dueOn, 'item' => $item, 'ticked' => $ticked,
                            'actor_party_id' => $partyId, 'operation_id' => $operationId, 'created_at' => now('UTC')])->save();

                        return new OperationResult('VISIT_READY_TICK_SAVED', ['business_id' => $business['id'], 'due_on' => $dueOn, 'item' => $item, 'ticked' => $ticked], 1);
                    });
            });
    }
}

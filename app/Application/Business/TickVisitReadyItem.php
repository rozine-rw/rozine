<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Business\Contracts\VisitReadyTicks;
use App\Domain\Operations\CommandRejection;

/**
 * `business.visit_ready.tick`: the Business ticks or unticks one item of its open visit-ready
 * checklist (BRS v3 VIS-R03) as ready, once per request id. Only an item of the checklist open
 * today, for its own instalment, can be ticked; the checklist is read under the caller's current
 * Business authority. A refusal comes back as a rejected result beside the item.
 */
final readonly class TickVisitReadyItem
{
    public function __construct(private GetBusinessRepayments $repayments, private VisitReadyTicks $ticks) {}

    /** @return array<string, mixed> */
    public function handle(int $userId, int $contextRevision, string $businessId, string $requestId, string $dueOn, string $item, bool $ticked): array
    {
        try {
            /** @var array{due_on: string, items: list<array{key: string, step: string}>}|null $open */
            $open = $this->repayments->handle($userId, $contextRevision, $businessId)['visit_ready'];
            if ($open === null || $open['due_on'] !== $dueOn) {
                throw new CommandRejection('VISIT_READY_CLOSED', 409, fieldErrors: ['item' => ['This checklist is no longer open. Refresh to see the current one.']]);
            }
            if (! in_array($item, array_column($open['items'], 'key'), true)) {
                throw new CommandRejection('VALIDATION_FAILED', 422, fieldErrors: ['item' => ['Choose an item on this month’s checklist.']]);
            }

            return $this->ticks->tick($userId, $contextRevision, $businessId, $requestId, $dueOn, $item, $ticked);
        } catch (CommandRejection $rejection) {
            return ['status' => 'rejected', 'code' => $rejection->reason, 'field_errors' => $rejection->fieldErrors];
        }
    }
}

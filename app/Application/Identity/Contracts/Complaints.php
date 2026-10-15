<?php

declare(strict_types=1);

namespace App\Application\Identity\Contracts;

use DateTimeImmutable;

/**
 * Complaints (BRS v2.0 CMP-R03). A customer opens one in-app and may add to it until it is
 * resolved; staff holding `complaints.answer` acknowledge, answer and resolve it. Every write is
 * journaled and idempotent by its request id, and answers a recorded operation result.
 *
 * @phpstan-type Message array{id: string, author: 'customer'|'staff', body: string, resolves: bool, at: string}
 * @phpstan-type Thread array{
 *     id: string, reference: string, status: 'awaiting'|'answered'|'resolved', opened_at: string, acknowledge_by: string, resolve_by: string,
 *     messages: list<Message>
 * }
 * @phpstan-type StaffThread array{
 *     id: string, reference: string, status: 'awaiting'|'answered'|'resolved', opened_at: string, acknowledge_by: string, resolve_by: string,
 *     messages: list<Message>, party_id: string, role: 'investor'|'business', customer: string
 * }
 * @phpstan-type Overdue array{
 *     id: string, reference: string, party_id: string, role: 'investor'|'business', customer: string, stage: 'acknowledge'|'resolve', due_at: string
 * }
 */
interface Complaints
{
    /**
     * Opens a complaint for the Investor's own Party with its first message.
     *
     * @return array<string, mixed>
     */
    public function open(int $userId, string $requestId, string $body): array;

    /**
     * Adds the Investor's message to one of their own complaints that is not resolved.
     *
     * @return array<string, mixed>
     */
    public function reply(int $userId, string $requestId, string $complaintId, string $body): array;

    /**
     * The Investor's own complaints, newest first, each with its whole conversation.
     *
     * @return list<Thread>
     */
    public function forInvestor(int $userId): array;

    /**
     * A staff message on a complaint that is not resolved; with `$resolves` it is the resolution.
     *
     * @return array<string, mixed>
     */
    public function answer(int $staffId, string $requestId, string $complaintId, string $body, bool $resolves): array;

    /**
     * Every complaint, unresolved first and then newest first, each with its whole conversation.
     *
     * @return list<StaffThread>
     */
    public function all(): array;

    /**
     * Each complaint past a clock at this instant: not acknowledged by its 4-hour mark, or
     * acknowledged but not resolved by its 48-hour mark.
     *
     * @return list<Overdue>
     */
    public function overdue(DateTimeImmutable $at): array;
}

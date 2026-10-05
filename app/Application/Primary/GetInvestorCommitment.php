<?php

declare(strict_types=1);

namespace App\Application\Primary;

use App\Application\Primary\Contracts\PrimaryCommitmentView;
use App\Domain\Operations\CommandRejection;

/** One commitment of the authenticated Investor, read under current authority. */
final class GetInvestorCommitment
{
    public function __construct(private PrimaryCommitmentView $view) {}

    /** @return array{identity_context_revision: int, commitment: array<string, mixed>} */
    public function handle(int $userId, ?int $contextRevision, string $commitmentId): array
    {
        return $this->view->show($userId, $contextRevision, $commitmentId);
    }

    /**
     * For the page: a commitment the Investor may not read (404) or whose state is not yet
     * sourced (409) becomes the page's own scoped refusal; anything else still throws.
     *
     * @return array{identity_context_revision: int, commitment: array<string, mixed>|null, refusal: array{code: string, status: int}|null}
     */
    public function page(int $userId, ?int $contextRevision, string $commitmentId): array
    {
        try {
            return [...$this->handle($userId, $contextRevision, $commitmentId), 'refusal' => null];
        } catch (CommandRejection $exception) {
            if (! in_array($exception->status, [404, 409], true)) {
                throw $exception;
            }

            return ['identity_context_revision' => $contextRevision ?? 0, 'commitment' => null,
                'refusal' => ['code' => $exception->reason, 'status' => $exception->status]];
        }
    }
}

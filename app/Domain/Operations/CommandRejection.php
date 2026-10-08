<?php

declare(strict_types=1);

namespace App\Domain\Operations;

use RuntimeException;

final class CommandRejection extends RuntimeException
{
    /**
     * @param  array<string, list<string>>  $fieldErrors
     * @param  array<string, mixed>  $data  Authorized facts retained in the refusal receipt.
     * @param  string|null  $policyVersion  The policy the refusal was decided under; the journal's default when null.
     */
    public function __construct(
        public readonly string $reason,
        public readonly int $status = 409,
        public readonly ?int $revision = null,
        public readonly array $fieldErrors = [],
        public readonly array $data = [],
        public readonly ?string $policyVersion = null,
    ) {
        parent::__construct($reason);
    }

    /** The same refusal, recorded under the policy version it was decided by. */
    public function underPolicy(string $policyVersion): self
    {
        return new self($this->reason, $this->status, $this->revision, $this->fieldErrors, $this->data, $policyVersion);
    }
}

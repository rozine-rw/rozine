<?php

declare(strict_types=1);

namespace App\Application\Auditor\Contracts;

interface AuditReportCryptography
{
    public function available(): bool;

    /** @param array<string, mixed> $payload
     * @return array{key_id: string, jws: string}
     */
    public function sign(array $payload): array;

    /** @param array<string, mixed> $payload */
    public function verify(string $keyId, string $jws, array $payload, bool $current = true): bool;
}

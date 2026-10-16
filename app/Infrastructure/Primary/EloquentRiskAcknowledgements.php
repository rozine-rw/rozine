<?php

declare(strict_types=1);

namespace App\Infrastructure\Primary;

use App\Application\Identity\AuthorizeActiveRole;
use App\Application\Operations\Contracts\OperationJournal;
use App\Application\Primary\Contracts\RiskAcknowledgements;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Domain\Primary\RiskStatements;
use App\Models\InvestorRiskAcknowledgement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** @phpstan-import-type AccessSnapshot from \App\Domain\Identity\ActiveRolePolicy */
final readonly class EloquentRiskAcknowledgements implements RiskAcknowledgements
{
    public function __construct(private AuthorizeActiveRole $authority, private OperationJournal $journal) {}

    public function acknowledge(int $userId, int $contextRevision, string $version, string $statementsSha256, string $requestId): array
    {
        return DB::transaction(fn (): array => $this->authority->handle($userId, 'investor', null, $contextRevision,
            function (array $identity) use ($userId, $contextRevision, $version, $statementsSha256, $requestId): array {
                $partyId = (string) $identity['party']['id'];

                return $this->journal->execute('party:'.$partyId, $userId, 'investor.risk.acknowledge', $requestId, 'party', $partyId,
                    ['identity_context_revision' => $contextRevision, 'version' => $version, 'statements_sha256' => $statementsSha256],
                    function (): void {}, function (string $operationId) use ($partyId, $version, $statementsSha256): OperationResult {
                        if ($version !== RiskStatements::VERSION || ! hash_equals(RiskStatements::sha256(), $statementsSha256)) {
                            throw new CommandRejection('RISK_STATEMENTS_STALE', 409);
                        }
                        $existing = $this->find($partyId);
                        $acknowledgement = $existing ?? $this->record($partyId, $operationId);

                        return new OperationResult('RISKS_ACKNOWLEDGED', ['acknowledgement_id' => $acknowledgement->id,
                            'version' => $acknowledgement->version, 'statements_sha256' => $acknowledgement->statements_sha256,
                            'acknowledged_at' => $acknowledgement->acknowledged_at->format('Y-m-d\TH:i:s.u\Z')], 1);
                    });
            }), 3);
    }

    public function read(int $userId, ?int $contextRevision): array
    {
        return DB::transaction(fn (): array => $this->authority->handle($userId, 'investor', null, $contextRevision,
            fn (array $identity): array => ['version' => RiskStatements::VERSION, 'statements_sha256' => RiskStatements::sha256(),
                'statements' => array_map(fn (string $key, string $text): array => ['key' => $key, 'text' => $text],
                    array_keys(RiskStatements::STATEMENTS), RiskStatements::STATEMENTS),
                'acknowledged' => $this->current((string) $identity['party']['id'])]));
    }

    public function current(string $partyId): bool
    {
        return $this->find($partyId) !== null;
    }

    private function find(string $partyId): ?InvestorRiskAcknowledgement
    {
        return InvestorRiskAcknowledgement::query()->where('party_id', strtolower($partyId))->where('version', RiskStatements::VERSION)
            ->where('statements_sha256', RiskStatements::sha256())->first();
    }

    private function record(string $partyId, string $operationId): InvestorRiskAcknowledgement
    {
        $at = now('UTC')->toDateTimeImmutable();
        $id = strtolower((string) Str::ulid());
        $digest = hash('sha256', implode("\n", ['investor-risk-acknowledgement-1', $id, $partyId, $operationId,
            RiskStatements::VERSION, RiskStatements::sha256(), $at->format('Y-m-d\TH:i:s.u\Z')]));
        $acknowledgement = new InvestorRiskAcknowledgement;
        $acknowledgement->forceFill(['id' => $id, 'party_id' => $partyId, 'operation_id' => $operationId, 'version' => RiskStatements::VERSION,
            'statements_sha256' => RiskStatements::sha256(), 'sha256' => $digest, 'acknowledged_at' => $at])->save();

        return $acknowledgement;
    }
}

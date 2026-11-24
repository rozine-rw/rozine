<?php

declare(strict_types=1);

namespace App\Infrastructure\Business;

use App\Application\Business\Contracts\AcademyAttemptStore;
use App\Application\Business\WithBusinessAuthority;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Operations\Contracts\OperationJournal;
use App\Domain\Business\ReadinessAcademy;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Models\BusinessAcademyAttempt;
use Closure;
use Illuminate\Support\Facades\DB;

/**
 * The Academy attempt adapter. An attempt is journaled under the acting Party against the
 * Business; its permitted input keeps only a digest of the answers. An advisory lock per business
 * and module means two sends of the same test never both land.
 */
final class EloquentAcademyAttemptStore implements AcademyAttemptStore
{
    public function __construct(private WithBusinessAuthority $authority, private OperationJournal $journal, private CanonicalJson $json) {}

    public function forBusiness(string $businessId): array
    {
        $records = BusinessAcademyAttempt::query()->where('business_id', $businessId)->orderBy('submitted_at')->orderBy('attempt')->get()->all();

        return array_values(array_map(fn (BusinessAcademyAttempt $record): array => ['module' => $record->module, 'attempt' => $record->attempt,
            'score' => $record->score, 'passed' => $record->passed, 'submitted_at' => $record->submitted_at->setTimezone('UTC')->format('Y-m-d\TH:i:s\Z')],
            $records));
    }

    public function submit(int $userId, int $contextRevision, string $businessId, string $module, string $requestId, array $input, Closure $decide): array
    {
        return $this->authority->handle($userId, $contextRevision, $businessId, 'business.view', null,
            function (array $business, array $identity) use ($userId, $contextRevision, $businessId, $module, $requestId, $input, $decide): array {
                $partyId = (string) ($identity['party']['id'] ?? throw new CommandRejection('BUSINESS_NOT_FOUND', 404));
                $id = (string) $business['id'];
                $permitted = ['identity_context_revision' => $contextRevision, 'module' => $module, 'answers_sha256' => hash('sha256', $this->json->encode($input))];

                return $this->journal->execute('party:'.$partyId, $userId, 'business.academy.test.submit', $requestId, 'business_profile', $id, $permitted,
                    function () use ($userId, $contextRevision, $businessId): void {
                        $this->authority->handle($userId, $contextRevision, $businessId, 'business.view', null, fn (): bool => true);
                    },
                    function (string $operationId) use ($id, $partyId, $module, $decide): OperationResult {
                        DB::select('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))', ['business-academy|'.$id.'|'.$module]);
                        $scored = $decide($id);
                        $record = new BusinessAcademyAttempt;
                        $record->forceFill(['business_id' => $id, 'module' => $module, 'attempt' => $scored['attempt'], 'questions' => $scored['questions'],
                            'answers' => $scored['answers'], 'score' => $scored['score'], 'passed' => $scored['passed'],
                            'policy_version' => ReadinessAcademy::POLICY_VERSION, 'submitted_by_party_id' => $partyId, 'operation_id' => $operationId,
                            'submitted_at' => now('UTC')])->save();

                        return new OperationResult('ACADEMY_TEST_SUBMITTED', ['business_id' => $id, 'module' => $module, 'attempt' => $scored['attempt'],
                            'score' => $scored['score'], 'passed' => $scored['passed']], 1);
                    });
            });
    }
}

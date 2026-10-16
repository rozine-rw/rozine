<?php

declare(strict_types=1);

namespace App\Infrastructure\Business;

use App\Application\Business\Contracts\BusinessContacts;
use App\Application\Business\WithBusinessAuthority;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Operations\Contracts\OperationJournal;
use App\Domain\Business\BusinessContact;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Models\BusinessContactVersion;
use Illuminate\Support\Facades\DB;

/**
 * The Business contact adapter. A save is journaled under the acting Party and targets the Business
 * profile; its permitted input keeps only a digest of the details, which are personal data. Versions
 * of one Business are numbered under an advisory lock so two saves never claim the same version.
 */
final class EloquentBusinessContacts implements BusinessContacts
{
    public function __construct(private WithBusinessAuthority $authority, private OperationJournal $journal, private CanonicalJson $json) {}

    public function current(string $businessId): ?array
    {
        $version = BusinessContactVersion::query()->where('business_id', $businessId)->orderByDesc('version')->first();

        return $version?->payload;
    }

    public function save(int $userId, int $contextRevision, string $businessId, string $requestId, array $input): array
    {
        return $this->authority->handle($userId, $contextRevision, $businessId, 'business.profile.update', null,
            function (array $business, array $identity) use ($userId, $contextRevision, $businessId, $requestId, $input): array {
                $partyId = (string) ($identity['party']['id'] ?? throw new CommandRejection('BUSINESS_NOT_FOUND', 404));
                $fields = array_map(fn (mixed $value): string => is_string($value) ? $value : '', array_intersect_key($input, array_flip(BusinessContact::FIELDS)));
                $permitted = ['identity_context_revision' => $contextRevision, 'contact_sha256' => hash('sha256', $this->json->encode($fields))];

                return $this->journal->execute('party:'.$partyId, $userId, 'business.profile.update', $requestId, 'business_profile', $business['id'], $permitted,
                    function () use ($userId, $contextRevision, $businessId): void {
                        $this->authority->handle($userId, $contextRevision, $businessId, 'business.profile.update', null, fn (): bool => true);
                    },
                    function (string $operationId) use ($business, $partyId, $input): OperationResult {
                        /** @var array<string, list<string>> $districts */
                        $districts = config('rwanda.districts');
                        $contact = BusinessContact::fromInput($input, $districts);
                        if (is_array($contact)) {
                            throw new CommandRejection('VALIDATION_FAILED', 422, fieldErrors: $contact);
                        }
                        DB::select('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))', ['business-contact|'.$business['id']]);
                        $version = (int) BusinessContactVersion::query()->where('business_id', $business['id'])->max('version') + 1;
                        $record = new BusinessContactVersion;
                        $record->forceFill(['business_id' => $business['id'], 'version' => $version, 'actor_party_id' => $partyId, 'operation_id' => $operationId,
                            'payload' => $contact->details, 'sha256' => hash('sha256', $this->json->encode($contact->details)),
                            'created_at' => now('UTC')])->save();

                        return new OperationResult('BUSINESS_CONTACT_SAVED', ['business_id' => $business['id'], 'version' => $version], 1);
                    });
            });
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity;

use App\Application\Identity\Contracts\LegalConsents;
use App\Models\LegalConsent;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/** @phpstan-import-type AgreedDocument from LegalConsents */
final class EloquentLegalConsents implements LegalConsents
{
    /** @param list<AgreedDocument> $documents */
    public function record(string $partyId, array $documents, string $source): void
    {
        $at = now('UTC')->toDateTimeImmutable();
        foreach ($documents as $document) {
            if ($this->has($partyId, $document)) {
                continue;
            }
            $id = strtolower((string) Str::ulid());
            $digest = hash('sha256', implode("\n", ['legal-consent-1', $id, $partyId, $document['key'], $document['version'],
                $document['sha256'], $source, $at->format('Y-m-d\TH:i:s.u\Z')]));
            (new LegalConsent)->forceFill(['id' => $id, 'party_id' => $partyId, 'document' => $document['key'], 'version' => $document['version'],
                'document_sha256' => $document['sha256'], 'source' => $source, 'sha256' => $digest, 'accepted_at' => $at])->save();
        }
    }

    public function partyFor(int $userId): ?string
    {
        $partyId = User::query()->whereKey($userId)->value('party_id');

        return is_string($partyId) ? $partyId : null;
    }

    /** @param list<AgreedDocument> $documents */
    public function covers(int $userId, array $documents): bool
    {
        $matches = LegalConsent::query()->whereColumn('legal_consents.party_id', 'users.party_id')
            ->where(function (Builder $query) use ($documents): void {
                foreach ($documents as $document) {
                    $query->orWhere(fn (Builder $match): Builder => $match->where('document', $document['key'])
                        ->where('version', $document['version'])->where('document_sha256', $document['sha256']));
                }
            })->selectRaw('count(*)');
        $account = User::query()->whereKey($userId)->leftJoin('parties', 'parties.id', '=', 'users.party_id')
            ->select('parties.kind')->selectSub($matches, 'agreed')->first();

        return $account?->getAttribute('kind') !== 'person' || (int) $account->getAttribute('agreed') === count($documents);
    }

    /** @param AgreedDocument $document */
    private function has(string $partyId, array $document): bool
    {
        return LegalConsent::query()->where('party_id', $partyId)->where('document', $document['key'])
            ->where('version', $document['version'])->where('document_sha256', $document['sha256'])->exists();
    }
}

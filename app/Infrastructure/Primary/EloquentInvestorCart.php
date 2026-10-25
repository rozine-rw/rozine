<?php

declare(strict_types=1);

namespace App\Infrastructure\Primary;

use App\Application\Identity\AuthorizeActiveRole;
use App\Application\Primary\Contracts\InvestorCart;
use App\Models\InvestorCartLine;
use App\Models\User;
use Closure;
use Illuminate\Support\Str;

/**
 * The Investor's cart lines, each read and written under current Investor authority (the actor and
 * Party locked through the change), so two tabs changing one cart apply one after the other.
 */
final readonly class EloquentInvestorCart implements InvestorCart
{
    public function __construct(private AuthorizeActiveRole $authority) {}

    public function count(int $userId): int
    {
        $partyId = User::query()->whereKey($userId)->value('party_id');

        return $partyId === null ? 0 : $this->countFor(strtolower((string) $partyId));
    }

    public function lines(int $userId, ?int $contextRevision): array
    {
        return $this->authority->handle($userId, 'investor', null, $contextRevision,
            fn (array $identity): array => array_values(InvestorCartLine::query()->where('party_id', strtolower((string) $identity['party']['id']))
                ->orderBy('created_at')->orderBy('id')->get()
                ->map(fn (InvestorCartLine $line): array => ['campaign_id' => $line->business_campaign_id, 'units' => $line->units])->all()));
    }

    public function put(int $userId, int $contextRevision, string $campaignId, int $units, Closure $admit): array
    {
        return $this->authority->handle($userId, 'investor', null, $contextRevision, function (array $identity) use ($campaignId, $units, $admit): array {
            $partyId = strtolower((string) $identity['party']['id']);
            $line = InvestorCartLine::query()->where('party_id', $partyId)->where('business_campaign_id', $campaignId)->first();
            $admit($line !== null, $this->countFor($partyId));
            $at = now('UTC')->toDateTimeImmutable();
            if ($line === null) {
                $line = new InvestorCartLine;
                $line->forceFill(['id' => strtolower((string) Str::ulid()), 'party_id' => $partyId, 'business_campaign_id' => $campaignId, 'created_at' => $at]);
            }
            $line->forceFill(['units' => $units, 'updated_at' => $at])->save();

            return ['campaign_id' => $campaignId, 'units' => $units, 'count' => $this->countFor($partyId)];
        });
    }

    public function remove(int $userId, int $contextRevision, string $campaignId): array
    {
        return $this->authority->handle($userId, 'investor', null, $contextRevision, function (array $identity) use ($campaignId): array {
            $partyId = strtolower((string) $identity['party']['id']);
            $removed = InvestorCartLine::query()->where('party_id', $partyId)->where('business_campaign_id', $campaignId)->delete() > 0;

            return ['campaign_id' => $campaignId, 'removed' => $removed, 'count' => $this->countFor($partyId)];
        });
    }

    public function bought(string $partyId, string $campaignId): void
    {
        InvestorCartLine::query()->where('party_id', strtolower($partyId))->where('business_campaign_id', $campaignId)->delete();
    }

    private function countFor(string $partyId): int
    {
        return InvestorCartLine::query()->where('party_id', $partyId)->count();
    }
}

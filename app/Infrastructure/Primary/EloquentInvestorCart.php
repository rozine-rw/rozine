<?php

declare(strict_types=1);

namespace App\Infrastructure\Primary;

use App\Application\Identity\AuthorizeActiveRole;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Primary\Contracts\InvestorCart;
use App\Models\InvestorCartLine;
use App\Models\InvestorCartRoot;
use App\Models\User;
use Closure;
use Illuminate\Support\Str;

/**
 * The Investor's cart lines, each read and written under current Investor authority (the actor and
 * Party locked through the change), so two tabs changing one cart apply one after the other. Each
 * real change also locks the cart's root, raises its revision once and records the hash of the
 * canonical snapshot (lines by campaign id with their units), in the same transaction.
 *
 * @phpstan-import-type CartLine from InvestorCart
 */
final readonly class EloquentInvestorCart implements InvestorCart
{
    /** The version of the canonical snapshot the cart hash covers. */
    private const string SNAPSHOT_VERSION = 'investor-cart-snapshot-1';

    public function __construct(private AuthorizeActiveRole $authority, private CanonicalJson $json) {}

    public function count(int $userId): int
    {
        $partyId = User::query()->whereKey($userId)->value('party_id');

        return $partyId === null ? 0 : $this->countFor(strtolower((string) $partyId));
    }

    public function lines(int $userId, ?int $contextRevision): array
    {
        return $this->authority->handle($userId, 'investor', null, $contextRevision,
            fn (array $identity): array => $this->linesFor(strtolower((string) $identity['party']['id'])));
    }

    public function snapshot(int $userId, ?int $contextRevision): array
    {
        return $this->authority->handle($userId, 'investor', null, $contextRevision, function (array $identity): array {
            $partyId = strtolower((string) $identity['party']['id']);
            $root = InvestorCartRoot::query()->where('party_id', $partyId)->first();
            $lines = $this->linesFor($partyId);

            return ['cart_id' => $root?->id, 'revision' => $root->revision ?? 0, 'sha256' => $root->sha256 ?? self::snapshotSha256($this->json, $lines), 'lines' => $lines];
        });
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
            if ($line->exists && $line->units === $units) {
                return ['campaign_id' => $campaignId, 'units' => $units, 'count' => $this->countFor($partyId)];
            }
            $line->forceFill(['units' => $units, 'updated_at' => $at])->save();
            $this->changed($partyId);

            return ['campaign_id' => $campaignId, 'units' => $units, 'count' => $this->countFor($partyId)];
        });
    }

    public function remove(int $userId, int $contextRevision, string $campaignId): array
    {
        return $this->authority->handle($userId, 'investor', null, $contextRevision, function (array $identity) use ($campaignId): array {
            $partyId = strtolower((string) $identity['party']['id']);
            $removed = InvestorCartLine::query()->where('party_id', $partyId)->where('business_campaign_id', $campaignId)->delete() > 0;
            if ($removed) {
                $this->changed($partyId);
            }

            return ['campaign_id' => $campaignId, 'removed' => $removed, 'count' => $this->countFor($partyId)];
        });
    }

    public function bought(string $partyId, string $campaignId): void
    {
        if (InvestorCartLine::query()->where('party_id', strtolower($partyId))->where('business_campaign_id', $campaignId)->delete() > 0) {
            $this->changed(strtolower($partyId));
        }
    }

    /** Raises the cart's revision once for a change to its lines and records the new snapshot hash. */
    private function changed(string $partyId): void
    {
        $at = now('UTC')->toDateTimeImmutable();
        $root = InvestorCartRoot::query()->where('party_id', $partyId)->lockForUpdate()->first();
        if ($root === null) {
            $root = new InvestorCartRoot;
            $root->forceFill(['id' => strtolower((string) Str::ulid()), 'party_id' => $partyId, 'revision' => 0, 'created_at' => $at]);
        }
        $root->forceFill(['revision' => $root->revision + 1, 'sha256' => self::snapshotSha256($this->json, $this->linesFor($partyId)), 'updated_at' => $at])->save();
    }

    /**
     * The Party's lines, oldest first.
     *
     * @return list<CartLine>
     */
    private function linesFor(string $partyId): array
    {
        return array_values(InvestorCartLine::query()->where('party_id', $partyId)->orderBy('created_at')->orderBy('id')->get()
            ->map(fn (InvestorCartLine $line): array => ['campaign_id' => $line->business_campaign_id, 'units' => $line->units])->all());
    }

    /**
     * The hash of the canonical snapshot: the lines by campaign id, whatever order they were added in.
     * The cart root migration hashes the carts that existed before it the same way.
     *
     * @param  list<CartLine>  $lines
     */
    public static function snapshotSha256(CanonicalJson $json, array $lines): string
    {
        usort($lines, fn (array $left, array $right): int => strcmp($left['campaign_id'], $right['campaign_id']));

        return hash('sha256', $json->encode(['version' => self::SNAPSHOT_VERSION, 'lines' => $lines]));
    }

    private function countFor(string $partyId): int
    {
        return InvestorCartLine::query()->where('party_id', $partyId)->count();
    }
}

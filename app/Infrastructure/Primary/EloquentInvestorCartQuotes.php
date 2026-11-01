<?php

declare(strict_types=1);

namespace App\Infrastructure\Primary;

use App\Application\Business\Contracts\PrimaryCampaignSource;
use App\Application\Identity\AuthorizeActiveRole;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Primary\Contracts\InvestorCartQuotes;
use App\Domain\Operations\CommandRejection;
use App\Domain\Primary\ReservationWindow;
use App\Models\BusinessCampaign;
use App\Models\InvestorCartLine;
use App\Models\InvestorCartQuote;
use App\Models\InvestorCartRoot;
use App\Models\User;
use Closure;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Whole-cart quotes kept as immutable rows. Lock order: the Business of every campaign in the cart
 * (by Business, then campaign), then Investor authority, then the cart root, then each campaign's
 * publication, the order the checkout uses for one campaign. The cart lines are first read without
 * authority only to know which Businesses to lock. Every change to the lines raises the cart
 * revision, so once authority and the root are held, a cart at the expected revision has exactly
 * the lines that were read, and any other revision is refused.
 *
 * The quote expires with the existing primary hold horizon: five minutes, or the earliest campaign
 * deadline if that is sooner (contract P383-03).
 *
 * @phpstan-import-type QuotedCart from InvestorCartQuotes
 */
final readonly class EloquentInvestorCartQuotes implements InvestorCartQuotes
{
    /** The version of the canonical object the quote's disclosure hash covers. */
    private const string DISCLOSURE_VERSION = 'cart-quote-disclosure-1';

    public function __construct(private AuthorizeActiveRole $authority, private PrimaryCampaignSource $campaigns, private CanonicalJson $json) {}

    public function quote(int $userId, int $contextRevision, int $expectedCartRevision, Closure $price): array
    {
        return DB::transaction(function () use ($userId, $contextRevision, $expectedCartRevision, $price): array {
            $partyId = strtolower((string) User::query()->whereKey($userId)->value('party_id'));
            $locked = BusinessCampaign::query()->whereIn('id', InvestorCartLine::query()->where('party_id', $partyId)->select('business_campaign_id'))
                ->orderBy('business_id')->orderBy('id')->pluck('id')->map(fn (mixed $id): string => (string) $id)->all();
            foreach ($locked as $campaignId) {
                $this->campaigns->lockBusiness($campaignId);
            }

            return $this->authority->handle($userId, 'investor', null, $contextRevision, function (array $identity) use ($expectedCartRevision, $price): array {
                $partyId = strtolower((string) $identity['party']['id']);
                $root = InvestorCartRoot::query()->where('party_id', $partyId)->lockForUpdate()->first();
                if ($root === null || $root->revision !== $expectedCartRevision) {
                    throw new CommandRejection('CART_REVISION_CONFLICT', 409, $root->revision ?? 0);
                }
                $lines = array_values(InvestorCartLine::query()->where('party_id', $partyId)->orderBy('created_at')->orderBy('id')->get()
                    ->map(fn (InvestorCartLine $line): array => ['id' => $line->id, 'campaign_id' => $line->business_campaign_id, 'units' => $line->units])->all());
                if ($lines === []) {
                    throw new CommandRejection('CART_EMPTY', 409, $root->revision);
                }

                return $this->priced($partyId, ['id' => $root->id, 'revision' => $root->revision, 'sha256' => $root->sha256, 'lines' => $lines], $price);
            });
        });
    }

    /**
     * Locks each campaign's publication, refusing the whole cart when any raise is closed or funded,
     * prices it and keeps the quote.
     *
     * @param  QuotedCart  $cart
     * @return array<string, mixed>
     */
    private function priced(string $partyId, array $cart, Closure $price): array
    {
        $inputs = [];
        $unavailable = [];
        foreach ($cart['lines'] as $line) {
            try {
                $inputs[$line['campaign_id']] = $this->campaigns->lock($line['campaign_id']);
            } catch (CommandRejection) {
                $unavailable[] = $line['campaign_id'];
            }
        }
        if ($unavailable !== []) {
            throw new CommandRejection('CART_LINE_UNAVAILABLE', 409, $cart['revision'], [], ['campaign_ids' => $unavailable]);
        }
        foreach ($inputs as $input) {
            $this->campaigns->rejectKnownConnections($input['business_id'], [$partyId]);
        }
        $body = $price($partyId, $cart, $inputs);
        $createdAt = now('UTC')->toDateTimeImmutable();
        $deadline = min(array_map(fn (array $input): DateTimeImmutable => $input['expires_at'], $inputs));
        $expiresAt = ReservationWindow::open($createdAt, $deadline)->expiresAt;
        $id = strtolower((string) Str::ulid());
        $disclosure = hash('sha256', $this->json->encode(['version' => self::DISCLOSURE_VERSION, 'contract_version' => self::CONTRACT_VERSION,
            'cart' => ['id' => $cart['id'], 'revision' => $cart['revision'], 'sha256' => $cart['sha256']], 'lines' => $body['lines'],
            'principal' => $body['principal'], 'earnings_fee' => $body['earnings_fee'], 'net_return' => $body['net_return'],
            'fee_band' => array_diff_key($body['fee_band'], ['capital_revision' => true, 'capital_sha256' => true])]));
        $quote = ['id' => $id, 'revision' => 1, 'contract_version' => self::CONTRACT_VERSION,
            'cart_id' => $cart['id'], 'cart_revision' => $cart['revision'], 'cart_sha256' => $cart['sha256'], ...$body,
            'disclosure_sha256' => $disclosure, 'created_at' => self::time($createdAt), 'expires_at' => self::time($expiresAt), 'confirmation_required' => true];
        $record = new InvestorCartQuote;
        $record->forceFill(['id' => $id, 'party_id' => $partyId, 'investor_cart_id' => $cart['id'], 'cart_revision' => $cart['revision'],
            'cart_sha256' => $cart['sha256'], 'contract_version' => self::CONTRACT_VERSION, 'payload' => $quote, 'disclosure_sha256' => $disclosure,
            'sha256' => hash('sha256', $this->json->encode($quote)), 'created_at' => $createdAt, 'expires_at' => $expiresAt])->save();

        return $quote;
    }

    private static function time(DateTimeImmutable $at): string
    {
        return $at->format('Y-m-d\TH:i:s.u\Z');
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Disbursement;

use Mmccook\JsonCanonicalizator\JsonCanonicalizatorFactory;

/**
 * The canonical digests an approval binds (C3 v2 §2e, #96 answer 5). The intent digest is sha256
 * over the RFC 8785 (JCS) form of exactly the facts the payment will carry, so a step-up proof
 * minted for one revision, amount, destination or set of commitments can never approve another.
 * The destination digest binds a verified payout destination to its Business, mandate, rail token,
 * environment and verification evidence; a masked label is never part of it.
 */
final readonly class IntentDigest
{
    public const array INTENT_FIELDS = ['disbursement_id', 'revision', 'campaign_id', 'exposure_reservation_id', 'amount', 'currency',
        'destination_sha256', 'commitments_digest', 'provider', 'environment'];

    public const array DESTINATION_FIELDS = ['destination_id', 'revision', 'business_id', 'mandate_id', 'rail', 'account_token_sha256',
        'environment', 'evidence_sha256', 'verified_at', 'expires_at'];

    /** @param array<string, string|int> $fields */
    public static function intent(array $fields): string
    {
        return self::digest($fields, self::INTENT_FIELDS, 'INTENT_DIGEST_INPUT_INVALID');
    }

    /** @param array<string, string|int> $fields */
    public static function destination(array $fields): string
    {
        return self::digest($fields, self::DESTINATION_FIELDS, 'DESTINATION_DIGEST_INPUT_INVALID');
    }

    /**
     * The digest of an ordered commitment list. Each entry is already a canonical map of the
     * immutable commitment facts (id, Party, originating operation, units, ordinals, rights, terms,
     * principal).
     *
     * @param  array<int, array<string, mixed>>  $commitments  an ordered list
     */
    public static function commitments(array $commitments): string
    {
        if ($commitments === [] || ! array_is_list($commitments)) {
            throw new DisbursementViolation('COMMITMENTS_DIGEST_INPUT_INVALID');
        }

        return hash('sha256', JsonCanonicalizatorFactory::getInstance()->canonicalize($commitments, false));
    }

    /**
     * @param  array<string, string|int>  $fields
     * @param  list<string>  $expected
     */
    private static function digest(array $fields, array $expected, string $violation): string
    {
        $keys = array_keys($fields);
        sort($keys);
        $sorted = $expected;
        sort($sorted);
        if ($keys !== $sorted) {
            throw new DisbursementViolation($violation);
        }
        foreach ($fields as $value) {
            if (is_string($value) ? $value === '' : $value < 0) {
                throw new DisbursementViolation($violation);
            }
        }

        return hash('sha256', JsonCanonicalizatorFactory::getInstance()->canonicalize((object) $fields, false));
    }
}

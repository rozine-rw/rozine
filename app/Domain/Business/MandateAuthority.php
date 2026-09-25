<?php

declare(strict_types=1);

namespace App\Domain\Business;

use App\Domain\Operations\CommandRejection;
use DateTimeImmutable;
use DateTimeZone;

/**
 * @phpstan-type Person array{party_id: string, name: string, roles: list<string>, permissions: list<string>}
 * @phpstan-type Terms array{people: list<Person>, required_signatories: list<string>, effective_at: string, expires_at: string|null, status: string, attested_complete: bool}
 * @phpstan-type Profile array{name: string, company_code: string|null, industry: string, district: string, established_year: int|null}
 */
final class MandateAuthority
{
    public const array PERMISSIONS = ['business.view', 'application.create', 'application.save', 'application.evaluate', 'application.sign', 'report.cosign'];

    /**
     * @param  Profile  $profile
     * @return Profile
     */
    public function profile(string $kind, array $profile, int $currentYear): array
    {
        if (! in_array($kind, ['person', 'organization'], true)
            || array_diff(array_keys($profile), ['name', 'company_code', 'industry', 'district', 'established_year']) !== []) {
            throw new CommandRejection('BUSINESS_PROFILE_INVALID', 422);
        }
        foreach (['name', 'industry', 'district'] as $field) {
            if (trim($profile[$field]) === '' || mb_strlen($profile[$field]) > 180) {
                throw new CommandRejection('BUSINESS_PROFILE_INVALID', 422);
            }
        }
        $code = $profile['company_code'] === null ? null : strtoupper($profile['company_code']);
        if (($kind === 'organization' && ($code === null || ! preg_match('/^[A-Z0-9][A-Z0-9.-]{1,127}$/D', $code)))
            || ($kind === 'person' && $code !== null)
            || ($profile['established_year'] !== null && ($profile['established_year'] < 1800 || $profile['established_year'] > $currentYear))) {
            throw new CommandRejection('BUSINESS_PROFILE_INVALID', 422);
        }

        return ['name' => trim($profile['name']), 'company_code' => $code, 'industry' => trim($profile['industry']),
            'district' => trim($profile['district']), 'established_year' => $profile['established_year']];
    }

    /**
     * @param  Terms  $terms
     * @return Terms
     */
    public function normalize(string $kind, string $entityPartyId, array $terms): array
    {
        if (! in_array($kind, ['person', 'organization'], true) || $terms['people'] === [] || $terms['required_signatories'] === []
            || ! $terms['attested_complete'] || ! in_array($terms['status'], ['active', 'revoked'], true)
            || ! $this->timestamp($terms['effective_at']) || ($terms['expires_at'] !== null
                && (! $this->timestamp($terms['expires_at']) || $terms['expires_at'] <= $terms['effective_at']))) {
            throw new CommandRejection('MANDATE_INVALID', 422);
        }
        $people = [];
        foreach ($terms['people'] as $person) {
            if (! preg_match('/^[0-9a-hjkmnp-tv-z]{26}$/D', $person['party_id']) || isset($people[$person['party_id']])
                || trim($person['name']) === '' || mb_strlen($person['name']) > 180 || $person['roles'] === []
                || array_diff($person['roles'], ['owner', 'beneficial_owner', 'controller', 'director', 'signatory', 'representative']) !== []
                || array_diff($person['permissions'], self::PERMISSIONS) !== []) {
                throw new CommandRejection('MANDATE_INVALID', 422);
            }
            if ($person['permissions'] !== [] && ! in_array('business.view', $person['permissions'], true)) {
                throw new CommandRejection('MANDATE_INVALID', 422);
            }
            $roles = array_values(array_unique($person['roles']));
            $permissions = array_values(array_unique($person['permissions']));
            sort($roles);
            sort($permissions);
            $people[$person['party_id']] = ['party_id' => $person['party_id'], 'name' => trim($person['name']), 'roles' => $roles, 'permissions' => $permissions];
        }
        $signers = array_values(array_unique($terms['required_signatories']));
        sort($signers);
        foreach ($signers as $id) {
            if (! isset($people[$id]) || ! in_array('signatory', $people[$id]['roles'], true)
                || ! in_array('application.sign', $people[$id]['permissions'], true) || ! in_array('report.cosign', $people[$id]['permissions'], true)) {
                throw new CommandRejection('MANDATE_INVALID', 422);
            }
        }
        if ($kind === 'person' && (array_keys($people) !== [$entityPartyId] || $signers !== [$entityPartyId]
            || ! in_array('owner', $people[$entityPartyId]['roles'], true))) {
            throw new CommandRejection('SOLE_TRADER_AUTHORITY_INVALID', 422);
        }
        ksort($people);

        return ['people' => array_values($people), 'required_signatories' => $signers,
            'effective_at' => $terms['effective_at'], 'expires_at' => $terms['expires_at'], 'status' => $terms['status'], 'attested_complete' => true];
    }

    /** @param list<Person> $people */
    public function requirePermission(array $people, string $partyId, string $permission): void
    {
        foreach ($people as $person) {
            if ($person['party_id'] === $partyId && in_array($permission, $person['permissions'], true)) {
                return;
            }
        }

        throw new CommandRejection('ACTION_FORBIDDEN', 403);
    }

    private function timestamp(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s\Z', $value, new DateTimeZone('UTC'));

        return $date !== false && $date->format('Y-m-d\TH:i:s\Z') === $value;
    }
}

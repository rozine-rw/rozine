<?php

declare(strict_types=1);

namespace App\Domain\Business;

use App\Domain\Operations\CommandRejection;

/**
 * @phpstan-import-type DocumentAcceptance from \App\Domain\Identity\ConsentDocuments
 * @phpstan-import-type DisclosureAcceptance from \App\Domain\Identity\ConsentDocuments
 *
 * @phpstan-type Input array{quote_id: string, quote_revision: int, evidence_version: string, mandate_version: string, accepted_principal: string, documents: list<DocumentAcceptance>, disclosures: list<DisclosureAcceptance>, terms: true, privacy: true, signature_name: string}
 */
final class ApplicationAcceptance
{
    public const string POLICY_VERSION = 'engineering-2026-09-23.4';

    /**
     * Validates the command boundary before nested consent values are accessed.
     * Typed names attest acceptance; only the locked verified Party and mandate grant authority.
     *
     * @param  array<string, mixed>  $input
     * @return Input
     */
    public function normalize(array $input): array
    {
        $keys = ['quote_id', 'quote_revision', 'evidence_version', 'mandate_version', 'accepted_principal', 'documents', 'disclosures', 'terms', 'privacy', 'signature_name'];
        if (count($input) !== count($keys) || array_diff(array_keys($input), $keys) !== []) {
            throw new CommandRejection('APPLICATION_ACCEPTANCE_INVALID', 422);
        }
        foreach (['quote_id', 'evidence_version', 'mandate_version', 'accepted_principal', 'signature_name'] as $field) {
            if (! is_string($input[$field])) {
                throw new CommandRejection('APPLICATION_ACCEPTANCE_INVALID', 422, fieldErrors: [$field => ['Use the current Review values.']]);
            }
        }
        if (! preg_match('/^[0-9a-hjkmnp-tv-z]{26}$/Di', $input['quote_id'])
            || ! is_int($input['quote_revision']) || $input['quote_revision'] < 1
            || ! preg_match('/^[a-f0-9]{64}$/D', $input['evidence_version'])
            || ! preg_match('/^[1-9][0-9]{0,9}$/D', $input['mandate_version'])
            || ! preg_match('/^(0|[1-9][0-9]{0,17})$/D', $input['accepted_principal'])) {
            throw new CommandRejection('APPLICATION_ACCEPTANCE_INVALID', 422);
        }
        if ($input['terms'] !== true || $input['privacy'] !== true) {
            throw new CommandRejection('APPLICATION_ACCEPTANCE_REQUIRED', 422,
                fieldErrors: ['terms' => ['Accept the current terms and privacy notice.']]);
        }
        $name = trim($input['signature_name']);
        if ($name === '' || mb_strlen($name) > 180 || ! mb_check_encoding($name, 'UTF-8') || preg_match('/[\x00-\x1f\x7f]/', $name)) {
            throw new CommandRejection('APPLICATION_ACCEPTANCE_INVALID', 422,
                fieldErrors: ['signature_name' => ['Enter your name to record your acceptance.']]);
        }
        $documents = $this->references($input['documents'], 'kind');
        $disclosures = $this->references($input['disclosures'], 'key');

        return ['quote_id' => $input['quote_id'], 'quote_revision' => $input['quote_revision'], 'evidence_version' => $input['evidence_version'],
            'mandate_version' => $input['mandate_version'], 'accepted_principal' => $input['accepted_principal'],
            'documents' => array_map(fn (array $item): array => ['kind' => $item['identity'], 'version' => $item['version'], 'sha256' => $item['sha256']], $documents),
            'disclosures' => array_map(fn (array $item): array => ['key' => $item['identity'], 'version' => $item['version'], 'sha256' => $item['sha256']], $disclosures),
            'terms' => true, 'privacy' => true, 'signature_name' => $name];
    }

    /**
     * @return list<array{identity: string, version: string, sha256: string}>
     */
    private function references(mixed $input, string $identity): array
    {
        if (! is_array($input) || ! array_is_list($input) || $input === [] || count($input) > 30) {
            throw new CommandRejection('APPLICATION_ACCEPTANCE_INVALID', 422);
        }
        $result = [];
        foreach ($input as $item) {
            if (! is_array($item) || count($item) !== 3 || array_diff(array_keys($item), [$identity, 'version', 'sha256']) !== []
                || ! is_string($item[$identity]) || ! is_string($item['version']) || ! is_string($item['sha256'])
                || ! preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._-]{0,79}$/D', $item[$identity])
                || ! preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._-]{0,79}$/D', $item['version'])
                || ! preg_match('/^[a-f0-9]{64}$/D', $item['sha256'])) {
                throw new CommandRejection('APPLICATION_ACCEPTANCE_INVALID', 422);
            }
            $result[] = ['identity' => $item[$identity], 'version' => $item['version'], 'sha256' => $item['sha256']];
        }

        return $result;
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Identity;

use App\Domain\Operations\CommandRejection;

/**
 * @phpstan-type Summary array{heading: string, body: string}
 * @phpstan-type DocumentInput array{kind: string, version: string, body: string, summary: list<Summary>}
 * @phpstan-type DisclosureInput array{key: string, version: string, text: string}
 * @phpstan-type Document array{kind: string, version: string, body: string, summary: list<Summary>, sha256: string}
 * @phpstan-type Disclosure array{key: string, version: string, text: string, sha256: string}
 * @phpstan-type DocumentAcceptance array{kind: string, version: string, sha256: string}
 * @phpstan-type DisclosureAcceptance array{key: string, version: string, sha256: string}
 */
final class ConsentDocuments
{
    /**
     * @param  list<DocumentInput>  $documents
     * @return list<Document>
     */
    public function documents(array $documents): array
    {
        $kinds = array_column($documents, 'kind');
        sort($kinds);
        if ($kinds !== ['privacy', 'terms']) {
            throw new CommandRejection('CONSENT_DOCUMENTS_INVALID', 422);
        }
        $result = [];
        foreach ($documents as $document) {
            $this->keys($document, ['kind', 'version', 'body', 'summary']);
            $this->version($document['version']);
            $this->text($document['body'], 200000);
            if ($document['summary'] === [] || count($document['summary']) > 30) {
                throw new CommandRejection('CONSENT_DOCUMENTS_INVALID', 422);
            }
            foreach ($document['summary'] as $clause) {
                $this->keys($clause, ['heading', 'body']);
                $this->text($clause['heading'], 200);
                $this->text($clause['body'], 4000);
            }
            $result[] = [...$document, 'sha256' => hash('sha256', $document['body'])];
        }
        usort($result, fn (array $left, array $right): int => $left['kind'] <=> $right['kind']);

        return $result;
    }

    /**
     * @param  list<DisclosureInput>  $disclosures
     * @return list<Disclosure>
     */
    public function disclosures(array $disclosures): array
    {
        if ($disclosures === [] || count($disclosures) > 30 || count(array_unique(array_column($disclosures, 'key'))) !== count($disclosures)) {
            throw new CommandRejection('CONSENT_DOCUMENTS_INVALID', 422);
        }
        $result = [];
        foreach ($disclosures as $disclosure) {
            $this->keys($disclosure, ['key', 'version', 'text']);
            $this->version($disclosure['key']);
            $this->version($disclosure['version']);
            $this->text($disclosure['text'], 10000);
            $result[] = [...$disclosure, 'sha256' => hash('sha256', $disclosure['text'])];
        }
        usort($result, fn (array $left, array $right): int => $left['key'] <=> $right['key']);

        return $result;
    }

    /**
     * @param  list<Document>  $documents
     * @param  list<Disclosure>  $disclosures
     * @param  list<DocumentAcceptance>  $acceptedDocuments
     * @param  list<DisclosureAcceptance>  $acceptedDisclosures
     */
    public function assertAccepted(array $documents, array $disclosures, array $acceptedDocuments, array $acceptedDisclosures): void
    {
        $expectedDocuments = array_map(fn (array $document): array => [$document['kind'], $document['version'], $document['sha256']], $documents);
        $expectedDisclosures = array_map(fn (array $disclosure): array => [$disclosure['key'], $disclosure['version'], $disclosure['sha256']], $disclosures);
        foreach ($acceptedDocuments as $document) {
            $this->keys($document, ['kind', 'version', 'sha256']);
        }
        foreach ($acceptedDisclosures as $disclosure) {
            $this->keys($disclosure, ['key', 'version', 'sha256']);
        }
        usort($acceptedDocuments, fn (array $left, array $right): int => $left['kind'] <=> $right['kind']);
        usort($acceptedDisclosures, fn (array $left, array $right): int => $left['key'] <=> $right['key']);
        $acceptedDocuments = array_map(fn (array $document): array => [$document['kind'], $document['version'], $document['sha256']], $acceptedDocuments);
        $acceptedDisclosures = array_map(fn (array $disclosure): array => [$disclosure['key'], $disclosure['version'], $disclosure['sha256']], $acceptedDisclosures);
        if ($expectedDocuments !== $acceptedDocuments || $expectedDisclosures !== $acceptedDisclosures) {
            throw new CommandRejection('DOCUMENT_VERSION_STALE');
        }
    }

    /**
     * @param  array<string, mixed>  $value
     * @param  list<string>  $keys
     */
    private function keys(array $value, array $keys): void
    {
        if (count($value) !== count($keys) || array_diff(array_keys($value), $keys) !== []) {
            throw new CommandRejection('CONSENT_DOCUMENTS_INVALID', 422);
        }
    }

    private function version(string $version): void
    {
        if (! preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._-]{0,79}$/D', $version)) {
            throw new CommandRejection('CONSENT_DOCUMENTS_INVALID', 422);
        }
    }

    private function text(string $text, int $maximum): void
    {
        if (trim($text) === '' || mb_strlen($text) > $maximum || ! mb_check_encoding($text, 'UTF-8')) {
            throw new CommandRejection('CONSENT_DOCUMENTS_INVALID', 422);
        }
    }
}

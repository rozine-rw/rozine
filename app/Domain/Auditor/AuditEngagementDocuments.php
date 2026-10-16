<?php

declare(strict_types=1);

namespace App\Domain\Auditor;

use App\Domain\Evidence\StatementAuditReview;
use App\Domain\Operations\CommandRejection;

/**
 * Retained platform MSA and minimum-procedure terms. Acceptance records the terms actually
 * presented; it does not attest that an assignment's professional work has been performed.
 *
 * @phpstan-type Document array{title: string, body: string, sha256: string}
 * @phpstan-type Documents array{master_services: Document, agreed_procedures: Document}
 * @phpstan-type Acceptance array{id: string, release_id: string, release_revision: int, release_sha256: string, accepted_at: string, sha256: string}
 */
final class AuditEngagementDocuments
{
    public const PROCEDURE = StatementAuditReview::PROCEDURE;

    public function version(mixed $version): string
    {
        if (! is_string($version) || preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,79}$/D', $version) !== 1) {
            throw new CommandRejection('AUDIT_ENGAGEMENT_INPUT_INVALID', 422, fieldErrors: ['version' => ['Supply a distinct, bounded document version.']]);
        }

        return $version;
    }

    /**
     * @param  array<string, mixed>  $documents
     * @return Documents
     */
    public function normalize(array $documents): array
    {
        $required = ['master_services', 'agreed_procedures'];
        if (array_diff(array_keys($documents), $required) !== [] || array_diff($required, array_keys($documents)) !== []) {
            throw new CommandRejection('AUDIT_ENGAGEMENT_INPUT_INVALID', 422, fieldErrors: ['documents' => ['Supply the full master services and agreed-procedures documents.']]);
        }

        return ['master_services' => $this->document($documents['master_services'], 'master_services'),
            'agreed_procedures' => $this->document($documents['agreed_procedures'], 'agreed_procedures')];
    }

    public function text(mixed $value, string $field, int $maximum, bool $multiline = false): string
    {
        if (! is_string($value) || ! mb_check_encoding($value, 'UTF-8') || trim($value) === '' || mb_strlen($value) > $maximum
            || preg_match($multiline ? '/[\p{Cf}\x00-\x08\x0B\x0C\x0E-\x1F\x7F-\x{009F}]/u' : '/[\p{Cc}\p{Cf}]/u', $value) === 1) {
            throw new CommandRejection('AUDIT_ENGAGEMENT_INPUT_INVALID', 422, fieldErrors: [$field => ['Supply complete text within the permitted length.']]);
        }

        return $value;
    }

    /** @return Document */
    private function document(mixed $document, string $kind): array
    {
        if (! is_array($document) || array_diff(array_keys($document), ['title', 'body']) !== []
            || ! array_key_exists('title', $document) || ! array_key_exists('body', $document)) {
            throw new CommandRejection('AUDIT_ENGAGEMENT_INPUT_INVALID', 422, fieldErrors: ['documents.'.$kind => ['Supply a title and the complete original text.']]);
        }
        $title = $this->text($document['title'], 'documents.'.$kind.'.title', 200);
        $body = $this->text($document['body'], 'documents.'.$kind.'.body', 200000, true);

        return ['title' => trim($title), 'body' => $body, 'sha256' => hash('sha256', $body)];
    }
}

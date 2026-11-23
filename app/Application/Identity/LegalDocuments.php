<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\LegalDocumentLibrary;
use App\Domain\Identity\LegalDocument;
use Illuminate\Support\Str;

/**
 * The current Terms, Privacy Notice and Disclaimers: their versions and digests, and each one as
 * a page. A person agrees by sending back the digest of every document they were shown.
 *
 * @phpstan-type CurrentDocument array{key: string, title: string, version: string, sha256: string}
 */
final class LegalDocuments
{
    public function __construct(private LegalDocumentLibrary $library) {}

    /** @return list<CurrentDocument> */
    public function current(): array
    {
        return array_map(fn (LegalDocument $document): array => ['key' => $document->value, 'title' => $document->title(),
            'version' => $document->version(), 'sha256' => $this->sha256($document)], LegalDocument::cases());
    }

    /**
     * The named document as a page, or null for a name that is not one of the documents.
     *
     * @return array{key: string, title: string, version: string, sha256: string, html: string}|null
     */
    public function pageFor(string $key): ?array
    {
        $document = LegalDocument::tryFrom($key);

        return $document === null ? null : $this->page($document);
    }

    /**
     * One document as a page. The wording is our own file; raw HTML in it is stripped all the same.
     *
     * @return array{key: string, title: string, version: string, sha256: string, html: string}
     */
    public function page(LegalDocument $document): array
    {
        return ['key' => $document->value, 'title' => $document->title(), 'version' => $document->version(), 'sha256' => $this->sha256($document),
            'html' => (string) Str::markdown($this->library->text($document), ['html_input' => 'strip', 'allow_unsafe_links' => false])];
    }

    /**
     * Whether these are the digests of exactly the current wording of every document, one per
     * document keyed by its name, and nothing more.
     *
     * @param  array<mixed>  $digests
     */
    public function allCurrent(array $digests): bool
    {
        if (count($digests) !== count(LegalDocument::cases())) {
            return false;
        }
        foreach (LegalDocument::cases() as $document) {
            $digest = $digests[$document->value] ?? null;
            if (! is_string($digest) || ! hash_equals($this->sha256($document), $digest)) {
                return false;
            }
        }

        return true;
    }

    private function sha256(LegalDocument $document): string
    {
        return hash('sha256', $this->library->text($document));
    }
}

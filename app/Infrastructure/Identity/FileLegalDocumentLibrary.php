<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity;

use App\Application\Identity\Contracts\LegalDocumentLibrary;
use App\Domain\Identity\LegalDocument;

/** The published wording lives in `resources/legal`, one Markdown file per document, versioned with the code. */
final class FileLegalDocumentLibrary implements LegalDocumentLibrary
{
    /** @var array<string, string> */
    private array $texts = [];

    public function text(LegalDocument $document): string
    {
        return $this->texts[$document->value] ??= (string) file_get_contents(resource_path("legal/{$document->value}.md"));
    }
}

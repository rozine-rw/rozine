<?php

declare(strict_types=1);

namespace App\Application\Identity\Contracts;

use App\Domain\Identity\LegalDocument;

/** Where the published wording of each legal document is kept. */
interface LegalDocumentLibrary
{
    /** The document's exact Markdown wording, the text a consent's digest is taken over. */
    public function text(LegalDocument $document): string;
}

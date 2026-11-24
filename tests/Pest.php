<?php

declare(strict_types=1);

use App\Application\Identity\LegalDocuments;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
 * Concurrency tests observe each other's writes across separate connections and
 * separate processes, so they cannot run inside the rolled-back transaction
 * that RefreshDatabase opens. They commit for real and truncate afterwards.
 */
pest()->extend(TestCase::class)
    ->use(DatabaseTruncation::class)
    ->in('Concurrency');

pest()->tia()->locally();

/**
 * The sign-up fields that tick the Terms, the Privacy Notice and the Disclaimers, each with the
 * digest of its current wording.
 *
 * @return array{legal_documents: array<string, string>, legal_agreements: array<string, string>}
 */
function legalConsentFields(): array
{
    $documents = app(LegalDocuments::class)->current();

    return ['legal_documents' => array_column($documents, 'sha256', 'key'),
        'legal_agreements' => array_fill_keys(array_column($documents, 'key'), '1')];
}

/** @return array<string, string> The digest of each current legal document, keyed by document. */
function legalDigests(): array
{
    return legalConsentFields()['legal_documents'];
}

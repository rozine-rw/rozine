<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\AcceptLegalDocuments;
use App\Application\Identity\LegalDocuments;
use App\Http\Requests\Identity\AcceptLegalDocumentsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The public Terms, Privacy Notice and Disclaimers pages, and the review page where a signed-in
 * person agrees to the current wording before they act in any app.
 */
class LegalDocumentController extends Controller
{
    public function __construct(private LegalDocuments $documents, private AcceptLegalDocuments $acceptance) {}

    public function show(Request $request, string $document): Response
    {
        $page = $this->documents->pageFor($document);
        abort_if($page === null, 404);

        return Inertia::render('legal/document', ['document' => $page, 'documents' => $this->links(),
            'review' => $request->user() !== null && ! $this->acceptance->satisfied((int) $request->user()->getAuthIdentifier())
                ? route('legal.review', absolute: false) : null]);
    }

    public function review(Request $request): Response|RedirectResponse
    {
        if ($this->acceptance->satisfied((int) $request->user()?->getAuthIdentifier())) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        return Inertia::render('legal/review', ['documents' => $this->links(), 'accept' => route('legal.accept', absolute: false)]);
    }

    public function accept(AcceptLegalDocumentsRequest $request): RedirectResponse
    {
        $this->acceptance->handle((int) $request->user()?->getAuthIdentifier(), (array) $request->validated('legal_documents'));

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /** @return list<array{key: string, title: string, version: string, sha256: string, url: string}> */
    private function links(): array
    {
        return array_map(fn (array $document): array => [...$document, 'url' => route('legal.'.$document['key'], absolute: false)],
            $this->documents->current());
    }
}

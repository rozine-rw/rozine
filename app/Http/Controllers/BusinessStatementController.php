<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Evidence\GetStatementEvidence;
use App\Application\Evidence\IngestStatement;
use App\Application\Evidence\ReadStatementOriginal;
use App\Application\Identity\AuthorizeActiveRole;
use App\Http\Requests\Business\IngestStatementRequest;
use App\Http\Resources\BusinessStatementsResource;
use App\Http\Resources\OperationResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Statement upload for the Business (BRS APP-B01). The web page posts a plain Inertia form and is
 * drawn again with the received statement; a refusal comes back as a form error on the same page.
 * The bearer API answers with the operation receipt.
 */
class BusinessStatementController extends Controller
{
    /** Refusals that are not about the file, in the words the page shows. */
    private const MESSAGES = [
        'VERSION_CONFLICT' => 'Another statement arrived in the meantime. Check the list and upload again if it is missing.',
    ];

    public function __construct(private AuthorizeActiveRole $identity) {}

    public function index(Request $request, GetStatementEvidence $statements): BusinessStatementsResource
    {
        $user = $request->user();
        abort_if($user === null || ! $user->tokenCan('business:read'), 403);
        $userId = (int) $user->getAuthIdentifier();
        $business = (string) $request->route('business');

        return new BusinessStatementsResource(['business_id' => $business,
            'manifest' => $statements->handle($userId, $this->identity->context($userId, 'business')['context_revision'], $business)]);
    }

    public function upload(IngestStatementRequest $request, IngestStatement $action): RedirectResponse|OperationResource
    {
        /** @var UploadedFile $file */
        $file = $request->validated('file');
        $result = $action->handle((int) $request->user()?->getAuthIdentifier(), (int) $request->validated('identity_context_revision'),
            (string) $request->route('business'), (int) $request->validated('expected_revision'), $file->getClientOriginalName(),
            (string) $file->get(), (string) $request->validated('request_id'));
        if ($request->routeIs('api.*')) {
            return new OperationResource($result);
        }
        if ($result['status'] !== 'completed') {
            /** @var array<string, list<string>> $fields */
            $fields = $result['field_errors'];
            throw ValidationException::withMessages($fields === []
                ? ['file' => [self::MESSAGES[$result['code']] ?? 'We could not keep this statement. Try again.']]
                : $fields);
        }

        return redirect()->back();
    }

    public function show(Request $request, ReadStatementOriginal $originals): Response
    {
        abort_if($request->routeIs('api.*') && ! $request->user()?->tokenCan('business:read'), 403);
        $userId = (int) $request->user()?->getAuthIdentifier();
        $document = $originals->handle($userId, $this->identity->context($userId, 'business')['context_revision'],
            (string) $request->route('business'), (string) $request->route('document'));
        $response = new Response($document['content'], 200, [
            'Content-Type' => $document['media_type'], 'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff', 'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
        $response->headers->set('Content-Disposition', HeaderUtils::makeDisposition('attachment', $document['filename']));

        return $response;
    }
}

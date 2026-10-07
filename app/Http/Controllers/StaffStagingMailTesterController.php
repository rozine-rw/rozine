<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Environment\ManageStagingMailTesters;
use App\Http\Requests\Staff\AddStagingMailTesterRequest;
use App\Http\Requests\Staff\RemoveStagingMailTesterRequest;
use App\Http\Resources\StaffStagingMailTestersResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Superadmin's named staging mail testers (`staging.mail.testers.manage`): who staging may email on
 * top of the server's own recipients. Staging only; elsewhere every route is not found. A recorded
 * refusal returns as a form error.
 */
class StaffStagingMailTesterController extends Controller
{
    /** Refusals in the words the page shows. */
    private const MESSAGES = [
        'TESTER_ALREADY_APPROVED' => 'This address is already an approved tester.',
        'TESTER_NOT_FOUND' => 'This tester was already removed.',
        'IDEMPOTENCY_CONFLICT' => 'That request was already used for different details. Try again.',
    ];

    public function __construct(private ManageStagingMailTesters $testers) {}

    public function index(Request $request): Response
    {
        abort_unless($this->testers->available(), 404);
        $search = trim((string) $request->query('q', ''));
        $page = ['testers' => $this->testers->list((int) $request->user()?->getAuthIdentifier(), $search),
            'server_recipients' => $this->testers->serverRecipients(), 'search' => mb_substr($search, 0, 120)];

        return Inertia::render('admin/staging-mail-testers', (new StaffStagingMailTestersResource($page))->resolve($request));
    }

    public function store(AddStagingMailTesterRequest $request): RedirectResponse
    {
        abort_unless($this->testers->available(), 404);

        return $this->answer($this->testers->add((int) $request->user()?->getAuthIdentifier(), (string) $request->validated('email'),
            (string) $request->validated('reason'), (string) $request->validated('request_id')));
    }

    public function remove(RemoveStagingMailTesterRequest $request): RedirectResponse
    {
        abort_unless($this->testers->available(), 404);

        return $this->answer($this->testers->remove((int) $request->user()?->getAuthIdentifier(), (string) $request->route('tester'),
            (string) $request->validated('reason'), (string) $request->validated('request_id')));
    }

    /** @param array<string, mixed> $result */
    private function answer(array $result): RedirectResponse
    {
        if ($result['status'] !== 'completed') {
            /** @var array<string, list<string>> $fieldErrors */
            $fieldErrors = $result['field_errors'] ?? [];

            throw ValidationException::withMessages($fieldErrors !== [] ? $fieldErrors
                : ['form' => [self::MESSAGES[$result['code']] ?? 'We could not record this change. Try again.']]);
        }

        return redirect()->route('staff.staging-mail-testers.index');
    }
}

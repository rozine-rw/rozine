<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Disbursement\ManageDisbursements;
use App\Http\Requests\Disbursement\ConfirmDisbursementStepUpRequest;
use App\Http\Requests\Disbursement\DisbursementCommandRequest;
use App\Http\Requests\Disbursement\ListDisbursementsRequest;
use App\Http\Requests\Disbursement\ShowDisbursementOperationRequest;
use App\Http\Resources\OperationResource;
use App\Http\Resources\StaffDisbursementsResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The staff disbursement console (C3 v2 §2e). Every command goes through the operation journal
 * under the staff actor; the step-up exchange answers `{proof, expires_at}` directly, is never
 * journaled and is never cached.
 */
class StaffDisbursementController extends Controller
{
    public function __construct(private ManageDisbursements $disbursements) {}

    public function index(ListDisbursementsRequest $request): StaffDisbursementsResource|Response
    {
        return $this->page($request, null);
    }

    public function show(ListDisbursementsRequest $request): StaffDisbursementsResource|Response
    {
        return $this->page($request, (string) $request->route('disbursement'));
    }

    public function command(DisbursementCommandRequest $request): OperationResource
    {
        $proof = $request->validated('step_up_proof');

        return $this->present($request, (string) $request->route('command'), $this->disbursements->command((int) $request->user()?->getAuthIdentifier(),
            (string) $request->route('disbursement'), (string) $request->route('command'), (int) $request->validated('expected_revision'),
            (string) $request->validated('reason'), (string) $request->validated('request_id'), is_string($proof) ? $proof : null));
    }

    public function stepUp(ConfirmDisbursementStepUpRequest $request): JsonResponse
    {
        $proof = $this->disbursements->stepUp((int) $request->user()?->getAuthIdentifier(), (string) $request->route('disbursement'),
            (int) $request->validated('expected_revision'), (string) $request->validated('intent_digest'), (string) $request->validated('code'));

        return response()->json($proof)->header('Cache-Control', 'no-store, private');
    }

    public function operation(ShowDisbursementOperationRequest $request): OperationResource
    {
        $command = (string) $request->validated('command');

        return $this->present($request, substr($command, 13), $this->disbursements->find((int) $request->user()?->getAuthIdentifier(), $command,
            (string) $request->route('request_id')));
    }

    private function page(Request $request, ?string $disbursementId): StaffDisbursementsResource|Response
    {
        $before = $request->query('before');
        $resource = new StaffDisbursementsResource($this->disbursements->page((int) $request->user()?->getAuthIdentifier(), $disbursementId,
            is_string($before) ? $before : null, (int) $request->query('limit', '25')));

        return $request->routeIs('api.*') ? $resource : Inertia::render('admin/disbursements', $resource->resolve($request));
    }

    /**
     * The recorded outcome with its immutable receipt, beside the disbursement's current facts read
     * afresh under the viewer's current authority.
     *
     * @param  array<string, mixed>  $result
     */
    private function present(Request $request, string $command, array $result): OperationResource
    {
        $data = $result['data'];
        $current = null;
        $receipt = $data['receipt'] ?? null;
        if (isset($data['disbursement_id'])) {
            $page = (new StaffDisbursementsResource($this->disbursements->page((int) $request->user()?->getAuthIdentifier(), $data['disbursement_id'], null, 1)))->resolve($request);
            $current = $page['disbursement'];
            $receipt = [...$data['receipt'], 'link' => StaffDisbursementsResource::lookup($request, $data['receipt']['request_id'], 'disbursement.'.$command)];
        }

        $facts = isset($data['causes']) ? ['causes' => $data['causes']] : [];

        return new OperationResource([...$result, 'data' => ['receipt' => $receipt, 'current' => $current, 'next' => null, ...$facts],
            'allowed_actions' => $current['allowed_actions'] ?? []]);
    }
}

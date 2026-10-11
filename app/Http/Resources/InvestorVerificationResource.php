<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * MVP-INVESTOR-SCR-03 for an individual: the person's own answers and upload states. `status` and
 * `decision_reason` are additive; nothing here says the person is verified until Compliance approves.
 * Only a rejection's reason reaches the person; an approval note stays with Compliance.
 *
 * @phpstan-type Submission array{
 *     revision: int,
 *     status: string,
 *     verified: bool,
 *     identity_context_revision: int,
 *     state: array{
 *         step: string, date_of_birth: string, id_type: string, id_number: string,
 *         uploads: array{front: string|null, back: string|null, selfie: string|null},
 *         decision: array{reason: string}|null
 *     }
 * }
 */
class InvestorVerificationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Submission $data */
        $data = $this->resource;
        $state = $data['state'];
        $api = $request->routeIs('api.*');
        $action = fn (string $name): array => ['url' => route(($api ? 'api.v1.' : '').$name, [], false), 'method' => 'post'];
        $upload = fn (?string $document): array => ['status' => $document === null ? 'missing' : ($data['status'] === 'approved' ? 'verified' : 'uploaded')];

        return ['investor_type' => 'individual', 'verified' => $data['verified'], 'identity_context_revision' => $data['identity_context_revision'], 'revision' => $data['revision'],
            'status' => $data['status'], 'decision_reason' => $data['status'] === 'rejected' ? $state['decision']['reason'] ?? null : null,
            'step' => $state['step'], 'country' => 'Rwanda', 'date_of_birth' => $state['date_of_birth'],
            'id_type' => $state['id_type'], 'id_number' => $state['id_number'],
            'uploads' => ['front' => $upload($state['uploads']['front']), 'back' => $upload($state['uploads']['back']), 'selfie' => $upload($state['uploads']['selfie'])],
            'links' => ['back' => $api ? null : ['url' => route('dashboard', [], false), 'method' => 'get']],
            'actions' => ['save' => $action('investor.verification.save'), 'upload' => $action('investor.verification.upload'),
                'submit' => $action('investor.verification.submit')]];
    }
}

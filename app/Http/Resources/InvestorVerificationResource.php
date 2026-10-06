<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * MVP-INVESTOR-SCR-03 for an individual: the person's own answers and upload states. `status` and
 * `decision_reason` are additive; nothing here says the person is verified until Compliance approves.
 *
 * @phpstan-type Submission array{
 *     revision: int,
 *     status: string,
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
        $upload = fn (?string $document): array => ['status' => $document === null ? 'missing' : ($data['status'] === 'approved' ? 'verified' : 'uploaded')];

        return ['investor_type' => 'individual', 'identity_context_revision' => $data['identity_context_revision'], 'revision' => $data['revision'],
            'status' => $data['status'], 'decision_reason' => $state['decision']['reason'] ?? null,
            'step' => $state['step'], 'country' => 'Rwanda', 'date_of_birth' => $state['date_of_birth'],
            'id_type' => $state['id_type'], 'id_number' => $state['id_number'],
            'uploads' => ['front' => $upload($state['uploads']['front']), 'back' => $upload($state['uploads']['back']), 'selfie' => $upload($state['uploads']['selfie'])],
            'links' => ['back' => ['url' => route('dashboard', [], false), 'method' => 'get']],
            'actions' => ['save' => self::action('investor.verification.save'), 'upload' => self::action('investor.verification.upload'),
                'submit' => self::action('investor.verification.submit')]];
    }

    /** @return array{url: string, method: 'post'} */
    private static function action(string $name): array
    {
        return ['url' => route($name, [], false), 'method' => 'post'];
    }
}

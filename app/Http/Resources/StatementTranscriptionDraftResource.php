<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * What a representative reviews before saving a transcription (contract initial-statement-audit-1 §5):
 * the exact originals and extraction revisions to save against, and the transcription being amended.
 * Assistance stays unavailable until a structured conversion exists; nothing here is verified.
 *
 * @phpstan-import-type TranscriptionDraft from \App\Application\Evidence\Contracts\StatementStore
 */
class StatementTranscriptionDraftResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var TranscriptionDraft $draft */
        $draft = $this->resource;
        $canSave = in_array('statement.reconcile', $draft['allowed_actions'], true)
            && (! $request->routeIs('api.*') || $request->user()?->tokenCan('business:command'));

        return [...$draft, 'allowed_actions' => $canSave ? ['statement.reconcile'] : [],
            'save' => $canSave ? ['url' => route('api.v1.business.statements.transcription.save', ['business' => $draft['business_id']], false), 'method' => 'post'] : null,
            'readiness' => ['url' => route('api.v1.business.statements.readiness', ['business' => $draft['business_id']], false), 'method' => 'get']];
    }
}

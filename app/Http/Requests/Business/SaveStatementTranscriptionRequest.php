<?php

declare(strict_types=1);

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A representative's reviewed transcription (contract initial-statement-audit-1 §5). Structure is checked here;
 * reconciliation, source binding and every precondition are decided and journaled under the supplied UUID.
 *
 * @phpstan-import-type Rail from \App\Domain\Evidence\StatementReconciliation
 * @phpstan-import-type Statement from \App\Domain\Evidence\StatementReconciliation
 * @phpstan-import-type DrawRef from \App\Domain\Evidence\StatementReconciliation
 * @phpstan-import-type ReviewedSave from \App\Application\Evidence\Contracts\StatementStore
 */
class SaveStatementTranscriptionRequest extends FormRequest
{
    private const ULID = 'regex:/^[0-9a-z]{26}$/';

    private const SHA256 = 'regex:/^[0-9a-f]{64}$/';

    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*')
            || ($this->user()->tokenCan('business:read') && $this->user()->tokenCan('business:command')));
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        $transaction = 'statements.*.transactions.*.';
        $rules = [
            'request_id' => ['required', 'uuid'], 'identity_context_revision' => ['required', 'integer', 'min:0'],
            'business_id' => ['sometimes', 'string', Rule::in([(string) $this->route('business')])],
            'expected_business_revision' => ['required', 'integer', 'min:0'], 'expected_mandate_version' => ['required', 'integer', 'min:0'],
            'expected_evidence_revision' => ['required', 'integer', 'min:0'],
            'expected_transcription_id' => ['present', 'nullable', 'string', self::ULID],
            'expected_transcription_sha256' => ['present', 'nullable', 'string', self::SHA256, 'required_with:expected_transcription_id'],
            'source_binding' => ['required', 'array:evidence_revision,manifest_sha256,originals,extractions'],
            'source_binding.evidence_revision' => ['required', 'integer', 'min:0'],
            'source_binding.manifest_sha256' => ['required', 'string', self::SHA256],
            'source_binding.originals' => ['present', 'list', 'max:500'],
            'source_binding.originals.*' => ['array:document_id,sha256'],
            'source_binding.originals.*.document_id' => ['required', 'string', self::ULID],
            'source_binding.originals.*.sha256' => ['required', 'string', self::SHA256],
            'source_binding.extractions' => ['present', 'list', 'max:500'],
            'source_binding.extractions.*' => ['array:document_id,extraction_id,revision,parser_version'],
            'source_binding.extractions.*.document_id' => ['required', 'string', self::ULID],
            'source_binding.extractions.*.extraction_id' => ['required', 'string', self::ULID],
            'source_binding.extractions.*.revision' => ['required', 'integer', 'min:1'],
            'source_binding.extractions.*.parser_version' => ['required', 'string', 'max:100'],
            'reviewed_against_originals' => ['required', 'boolean'],
            'rails' => ['required', 'list', 'max:50'], 'rails.*' => ['array:id,active_from,active_until'],
            'rails.*.id' => ['required', 'string', 'max:100'], 'rails.*.active_from' => ['required', 'string', 'max:7'],
            'rails.*.active_until' => ['present', 'nullable', 'string', 'max:7'],
            'months' => ['required', 'list', 'max:36'], 'months.*' => ['required', 'string', 'max:7'],
            'statements' => ['required', 'list', 'max:1800'],
            'statements.*' => ['array:rail_id,month,opening_balance,closing_balance,source_ids,transactions'],
            'statements.*.rail_id' => ['required', 'string', 'max:100'], 'statements.*.month' => ['required', 'string', 'max:7'],
            'statements.*.opening_balance' => ['required', 'string', 'max:66'], 'statements.*.closing_balance' => ['required', 'string', 'max:66'],
            'statements.*.source_ids' => ['required', 'list', 'max:100'], 'statements.*.source_ids.*' => ['required', 'string', 'max:26'],
            'statements.*.transactions' => ['present', 'list', 'max:5000'],
            'statements.*.transactions.*' => ['array:reference,date,amount,classification,source_ids,return_of,transfer_of,exception_id'],
            $transaction.'reference' => ['required', 'string', 'max:200'], $transaction.'date' => ['required', 'string', 'max:10'],
            $transaction.'amount' => ['required', 'string', 'max:66'], $transaction.'classification' => ['required', 'string', 'max:50'],
            $transaction.'source_ids' => ['required', 'list', 'max:100'], $transaction.'source_ids.*' => ['required', 'string', 'max:26'],
            $transaction.'exception_id' => ['present', 'nullable', 'string', 'max:26'],
        ];
        foreach (['return_of', 'transfer_of'] as $reference) {
            $rules[$transaction.$reference] = ['present', 'nullable', 'array:rail_id,month,reference'];
            foreach (['rail_id' => 100, 'month' => 7, 'reference' => 200] as $key => $max) {
                $rules[$transaction.$reference.'.'.$key] = ['required_with:'.$transaction.$reference, 'string', 'max:'.$max];
            }
        }

        return $rules;
    }

    /** @return list<Rail> */
    public function rails(): array
    {
        /** @var list<array{id: string, active_from: string, active_until: string|null}> $rails */
        $rails = $this->validated('rails');

        return array_map(fn (array $rail): array => ['id' => $rail['id'], 'active_from' => $rail['active_from'], 'active_until' => $rail['active_until']], $rails);
    }

    /** @return list<string> */
    public function months(): array
    {
        /** @var list<string> $months */
        $months = $this->validated('months');

        return $months;
    }

    /** @return list<Statement> */
    public function statements(): array
    {
        /** @var list<array<string, mixed>> $statements */
        $statements = $this->validated('statements');

        /** @var list<Statement> $mapped */
        $mapped = array_map(fn (array $statement): array => ['rail_id' => $statement['rail_id'], 'month' => $statement['month'],
            'opening_balance' => $statement['opening_balance'], 'closing_balance' => $statement['closing_balance'], 'source_ids' => $statement['source_ids'],
            'transactions' => array_map(fn (array $transaction): array => ['reference' => $transaction['reference'], 'date' => $transaction['date'],
                'amount' => $transaction['amount'], 'classification' => $transaction['classification'], 'source_ids' => $transaction['source_ids'],
                'return_of' => $this->drawReference($transaction['return_of']), 'transfer_of' => $this->drawReference($transaction['transfer_of']),
                'exception_id' => $transaction['exception_id']], $statement['transactions'])], $statements);

        return $mapped;
    }

    /** @return ReviewedSave */
    public function review(): array
    {
        /** @var array{evidence_revision: int, manifest_sha256: string, originals: list<array{document_id: string, sha256: string}>, extractions: list<array{document_id: string, extraction_id: string, revision: int, parser_version: string}>} $binding */
        $binding = $this->validated('source_binding');

        return ['expected_business_revision' => (int) $this->validated('expected_business_revision'),
            'expected_mandate_version' => (int) $this->validated('expected_mandate_version'),
            'expected_transcription_id' => $this->validated('expected_transcription_id'),
            'expected_transcription_sha256' => $this->validated('expected_transcription_sha256'),
            'source_binding' => ['evidence_revision' => (int) $binding['evidence_revision'], 'manifest_sha256' => $binding['manifest_sha256'],
                'originals' => array_map(fn (array $original): array => ['document_id' => $original['document_id'], 'sha256' => $original['sha256']], $binding['originals']),
                'extractions' => array_map(fn (array $extraction): array => ['document_id' => $extraction['document_id'], 'extraction_id' => $extraction['extraction_id'],
                    'revision' => (int) $extraction['revision'], 'parser_version' => $extraction['parser_version']], $binding['extractions'])],
            'reviewed_against_originals' => $this->boolean('reviewed_against_originals')];
    }

    /**
     * @param  array{rail_id: string, month: string, reference: string}|null  $reference
     * @return DrawRef|null
     */
    private function drawReference(?array $reference): ?array
    {
        return $reference === null ? null : ['rail_id' => $reference['rail_id'], 'month' => $reference['month'], 'reference' => $reference['reference']];
    }
}

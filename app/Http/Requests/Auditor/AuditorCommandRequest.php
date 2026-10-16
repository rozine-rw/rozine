<?php

declare(strict_types=1);

namespace App\Http\Requests\Auditor;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The envelope every Auditor profile command carries (auditor-filing-v1 point 1): the identity
 * context it was read under, its target's revision and its own idempotency key. The command's own
 * facts are the domain's to refuse, so those refusals are journalled and replay under the key.
 */
abstract class AuditorCommandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*') || $this->user()->tokenCan('auditor:command'));
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            ...$this->commandRules(),
            'identity_context_revision' => ['required', 'integer', 'min:0'],
            'expected_revision' => ['required', 'integer', 'min:0'],
            'request_id' => ['required', 'uuid'],
        ];
    }

    /** @return array<string, list<string>> */
    abstract protected function commandRules(): array;
}

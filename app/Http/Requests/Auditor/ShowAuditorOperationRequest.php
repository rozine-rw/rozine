<?php

declare(strict_types=1);

namespace App\Http\Requests\Auditor;

use Illuminate\Foundation\Http\FormRequest;

/** The operation lookup: the command name a request ID was sent under. */
class ShowAuditorOperationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*') || $this->user()->tokenCan('auditor:read'));
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'command' => ['required', 'string', 'max:100'],
        ];
    }
}

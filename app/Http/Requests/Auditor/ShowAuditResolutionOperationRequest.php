<?php

declare(strict_types=1);

namespace App\Http\Requests\Auditor;

use Illuminate\Foundation\Http\FormRequest;

class ShowAuditResolutionOperationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*') || $this->user()->tokenCan('staff:audit:read'));
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['command' => ['required', 'string', 'max:100']];
    }
}

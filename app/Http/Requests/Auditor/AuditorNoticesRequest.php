<?php

declare(strict_types=1);

namespace App\Http\Requests\Auditor;

use Illuminate\Foundation\Http\FormRequest;

/** Reading the Audit Partner's own notices, and marking one or all of them read. */
class AuditorNoticesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $command = $this->isMethod('POST');

        return $this->user() !== null && (! $this->routeIs('api.*')
            || ($this->user()->tokenCan('auditor:read') && (! $command || $this->user()->tokenCan('auditor:command'))));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}

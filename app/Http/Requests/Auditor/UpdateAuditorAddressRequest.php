<?php

declare(strict_types=1);

namespace App\Http\Requests\Auditor;

/** The registered address fields; the store checks the district belongs to the province. */
class UpdateAuditorAddressRequest extends AuditorCommandRequest
{
    /** @return array<string, list<string>> */
    protected function commandRules(): array
    {
        return [
            'address' => ['required', 'array'],
            'address.province' => ['present', 'nullable', 'string', 'max:40'],
            'address.district' => ['present', 'nullable', 'string', 'max:40'],
            'address.sector' => ['present', 'nullable', 'string', 'max:80'],
            'address.cell' => ['present', 'nullable', 'string', 'max:80'],
            'address.street' => ['nullable', 'string', 'max:160'],
        ];
    }
}

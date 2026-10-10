<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

class ListInvestorDirectoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*') || $this->user()->tokenCan('staff:investors:read'));
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['chip' => ['sometimes', 'string', 'in:all,verified,pending,kyc_overdue,frozen,restricted'], 'sort' => ['sometimes', 'string', 'in:portfolio,name'],
            'q' => ['sometimes', 'nullable', 'string', 'max:120'], 'investor' => ['sometimes', 'string', 'ulid'], 'verification' => ['sometimes', 'string', 'ulid']];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\Auditor;

use Illuminate\Foundation\Http\FormRequest;

class ShowAuditReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*') || $this->user()->tokenCan('auditor:read'));
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['step' => ['sometimes', 'string', 'in:review,check_in,photos,ledger,seal,statements,count'],
            'observed_stock' => ['sometimes', 'nullable', 'string', 'max:64'],
            'cash' => ['sometimes', 'nullable', 'string', 'max:64'], 'stock_units' => ['sometimes', 'nullable', 'string', 'max:64']];
    }
}

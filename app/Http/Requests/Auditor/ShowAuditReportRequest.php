<?php

declare(strict_types=1);

namespace App\Http\Requests\Auditor;

use App\Domain\Auditor\AuditProcedure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShowAuditReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*') || $this->user()->tokenCan('auditor:read'));
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        $procedure = new AuditProcedure;
        $steps = array_unique([...$procedure->steps('baseline'), ...$procedure->steps('late'), ...$procedure->steps('monthly')]);

        return ['step' => ['sometimes', 'string', Rule::in($steps)],
            'observed_stock' => ['sometimes', 'nullable', 'string', 'max:64'],
            'cash' => ['sometimes', 'nullable', 'string', 'max:64'], 'stock_units' => ['sometimes', 'nullable', 'string', 'max:64']];
    }
}

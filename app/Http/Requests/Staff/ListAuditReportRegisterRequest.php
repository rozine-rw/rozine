<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

/** The audit report register's view: every audit, or those owed by a partner, awaiting co-sign, published or in dispute. */
class ListAuditReportRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['chip' => ['sometimes', 'string', 'in:all,owed,awaiting_cosign,published,disputed']];
    }
}

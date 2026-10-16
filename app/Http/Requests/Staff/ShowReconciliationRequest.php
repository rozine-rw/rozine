<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

/** The Reconciliation page, on the web or with a `staff:reconciliation:read` API token. */
class ShowReconciliationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*') || $this->user()->tokenCan('staff:reconciliation:read'));
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [];
    }
}

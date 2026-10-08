<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

/** The Operations Center and its capital chart window, as the chart's From and To pickers send it. */
class ShowStaffDashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*') || $this->user()->tokenCan('staff:dashboard:read'));
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['from' => ['sometimes', 'nullable', 'date_format:Y-m-d\TH:i'], 'to' => ['sometimes', 'nullable', 'date_format:Y-m-d\TH:i']];
    }
}

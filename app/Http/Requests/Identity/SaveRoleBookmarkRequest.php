<?php

declare(strict_types=1);

namespace App\Http\Requests\Identity;

use Illuminate\Foundation\Http\FormRequest;

class SaveRoleBookmarkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*') || $this->user()->tokenCan('identity:access'));
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'role' => ['required', 'in:investor,business,auditor'],
            'route' => ['required', 'string', 'max:100'],
            'parameters' => ['present', 'array', 'max:0'],
            'query' => ['present', 'array:section'],
            'query.section' => ['sometimes', 'required', 'in:overview,access'],
            'expected_revision' => ['required', 'integer', 'min:0'],
            'request_id' => ['required', 'uuid'],
        ];
    }
}

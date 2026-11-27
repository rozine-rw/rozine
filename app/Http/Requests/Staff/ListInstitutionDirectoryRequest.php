<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

class ListInstitutionDirectoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['status' => ['sometimes', 'string', 'in:all,approved,submitted,changes_requested,draft,rejected'],
            'q' => ['sometimes', 'nullable', 'string', 'max:120'],
            'institution' => ['sometimes', 'string', 'ulid'], 'application' => ['sometimes', 'string', 'ulid']];
    }
}

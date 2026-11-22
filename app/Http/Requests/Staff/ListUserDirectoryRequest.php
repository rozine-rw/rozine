<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

class ListUserDirectoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['role' => ['sometimes', 'string', 'in:all,investor,business,auditor,staff,no_role'],
            'kyc' => ['sometimes', 'string', 'in:any,verified,in_review,in_progress,rejected,not_started'],
            'sort' => ['sometimes', 'string', 'in:newest,oldest,name'],
            'q' => ['sometimes', 'nullable', 'string', 'max:120'], 'user' => ['sometimes', 'integer', 'min:1']];
    }
}

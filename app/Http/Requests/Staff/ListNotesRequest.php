<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

/** The Notes register's tab, search, how many to show and the note whose holders are open, on the web console. */
class ListNotesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return ['tab' => ['sometimes', 'string', 'in:all,live,repaying,matured,failed'], 'q' => ['sometimes', 'nullable', 'string', 'max:120'],
            'sort' => ['sometimes', 'string', 'in:newest,raised,yield,funded,investors,term'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:500'], 'note' => ['sometimes', 'string', 'ulid']];
    }
}

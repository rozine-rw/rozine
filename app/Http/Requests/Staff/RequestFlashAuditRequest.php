<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

/** A staff Flash audit request from a Business's 360: why it is needed, under one request. */
class RequestFlashAuditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['request_id' => ['required', 'uuid'], 'reason' => ['nullable', 'string', 'max:10000']];
    }
}

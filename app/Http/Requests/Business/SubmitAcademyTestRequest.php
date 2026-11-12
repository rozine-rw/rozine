<?php

declare(strict_types=1);

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;

/** `business.academy.test.submit` input. The draw, the score and whether a test is open are decided on the server. */
class SubmitAcademyTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['request_id' => ['required', 'uuid'], 'identity_context_revision' => ['required', 'integer', 'min:0'],
            'attempt' => ['required', 'integer', 'min:1'], 'answers' => ['present', 'array']];
    }
}

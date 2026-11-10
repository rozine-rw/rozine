<?php

declare(strict_types=1);

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;

/** `business.monthly_submission.send` input. The window, the figures and the forecast check are decided on the server. */
class SubmitMonthlyFiguresRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['request_id' => ['required', 'uuid'], 'identity_context_revision' => ['required', 'integer', 'min:0'],
            'money_in' => ['present'], 'costs' => ['present', 'array'], 'paid_to_owners' => ['present'], 'forecast' => ['present'],
            'order' => ['nullable', 'string', 'max:1000'], 'changes' => ['present', 'array']];
    }
}

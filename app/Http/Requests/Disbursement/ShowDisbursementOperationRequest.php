<?php

declare(strict_types=1);

namespace App\Http\Requests\Disbursement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShowDisbursementOperationRequest extends FormRequest
{
    public const array COMMANDS = ['disbursement.authorize', 'disbursement.approve', 'disbursement.reject', 'disbursement.hold',
        'disbursement.release_hold', 'disbursement.requery'];

    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*') || $this->user()->tokenCan('staff:disbursements:read'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['command' => ['required', 'string', Rule::in(self::COMMANDS)]];
    }
}

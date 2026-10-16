<?php

declare(strict_types=1);

namespace App\Http\Requests\Investor;

use Illuminate\Foundation\Http\FormRequest;

/** Reading the account's own notices, and marking one or all of them read. */
class NoticesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $command = $this->isMethod('POST');

        return $this->user() !== null && (! $this->routeIs('api.*')
            || ($this->user()->tokenCan('investor:read') && (! $command || $this->user()->tokenCan('investor:command'))));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}

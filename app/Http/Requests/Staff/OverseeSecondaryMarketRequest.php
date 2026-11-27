<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

/**
 * A secondary market halt, lift or staff listing cancel (SEC-R08, SEC-R09): the written reason, the
 * request id that names one effect, and for a halt or lift the note it covers (none for the whole
 * market). The permission is decided on the server.
 */
class OverseeSecondaryMarketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'max:1000'], 'request_id' => ['required', 'uuid'],
            'note_id' => ['sometimes', 'nullable', 'string', 'regex:/^[0-9A-Za-z]{26}$/']];
    }
}

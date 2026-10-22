<?php

declare(strict_types=1);

namespace App\Http\Requests\Investor;

use App\Application\Wallet\Contracts\WalletStore;
use Illuminate\Foundation\Http\FormRequest;

class ShowWalletRequest extends FormRequest
{
    /** The windows the wallet history narrows to, newest first; `all` leaves it whole. */
    public const array PERIODS = WalletStore::HISTORY_PERIODS;

    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*') || $this->user()->tokenCan('investor:read'));
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['identity_context_revision' => ['sometimes', 'integer', 'min:0'], 'kind' => ['sometimes', 'nullable', 'string', 'in:deposit,withdraw'],
            'amount' => ['sometimes', 'nullable', 'string', 'regex:/^[0-9]{0,15}$/D'], 'movement' => ['sometimes', 'string', 'in:external,internal'],
            'period' => ['sometimes', 'string', 'in:'.implode(',', self::PERIODS)],
            'before' => ['sometimes', 'ulid'], 'receipt' => ['sometimes', 'ulid']];
    }
}

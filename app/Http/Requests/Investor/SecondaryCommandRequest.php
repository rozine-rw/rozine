<?php

declare(strict_types=1);

namespace App\Http\Requests\Investor;

use Illuminate\Foundation\Http\FormRequest;

/**
 * A resale command from the Market page (BRS v3.0 §12): listing a lot's units at a whole-franc price
 * per unit, reserving units of a listing, settling a reservation, or withdrawing a listing. The
 * route names the listing; the Party, the cap, the window and the limits are resolved on the server.
 */
class SecondaryCommandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        $common = ['request_id' => ['required', 'uuid'], 'identity_context_revision' => ['required', 'integer', 'min:0']];
        $units = ['required', 'string', 'regex:/^[1-9][0-9]{0,8}$/D'];

        return match (true) {
            $this->routeIs('*.market_list') => [...$common, 'lot_id' => ['required', 'ulid'], 'units' => $units,
                'price' => ['required', 'string', 'regex:/^[1-9][0-9]{0,11}$/D']],
            $this->routeIs('*.market_reserve') => [...$common, 'units' => $units],
            $this->routeIs('*.market_settle') => [...$common, 'reservation_id' => ['required', 'ulid']],
            default => $common,
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Domain\Pulse\PulseSector;
use App\Domain\Pulse\PulseUnderwriting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait PulseBusinessValidationRules
{
    /**
     * Get the self-reported figures accepted by the Pulse simulation.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function businessSizingRules(): array
    {
        return [
            'annual_revenue' => ['required', 'integer', 'min:'.PulseUnderwriting::MINIMUM_REVENUE],
            'annual_costs' => ['required', 'integer', 'min:1', 'lt:annual_revenue'],
            'sector' => ['required', Rule::enum(PulseSector::class)],
            'registered_year' => [
                'required',
                'integer',
                'min:'.PulseUnderwriting::EARLIEST_REGISTRATION_YEAR,
                'max:'.now()->year,
            ],
            'term_months' => ['required', 'integer', Rule::in(PulseUnderwriting::TERMS)],
        ];
    }

    /** @return array<string, string> */
    protected function businessSizingMessages(): array
    {
        return [
            'annual_revenue.min' => 'Rozine sizes businesses making RWF 15M or more over 12 months.',
            'annual_costs.lt' => 'Your costs have to be lower than your revenue to pre-qualify.',
        ];
    }

    public function sector(): PulseSector
    {
        return PulseSector::from($this->string('sector')->toString());
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Pulse;

use App\Application\Pulse\Contracts\PulseSignupRepository;
use App\Domain\Pulse\PulseSector;

final class RegisterPulseBusiness
{
    public function __construct(
        private PulseSignupRepository $signups,
        private PreviewPulseBusiness $previewBusiness,
    ) {}

    /**
     * @param  array{name: string, contact_method: string, contact: string, province: string, district: string, ip_address: string|null, user_agent: string|null}  $signup
     * @return array<string, mixed>
     */
    public function handle(
        array $signup,
        int $annualRevenue,
        int $annualCosts,
        PulseSector $sector,
        int $registeredYear,
        int $termMonths,
        bool $listed,
        int $currentYear,
    ): array {
        $sizing = $this->previewBusiness->handle(
            $annualRevenue,
            $annualCosts,
            $sector,
            $registeredYear,
            $termMonths,
            $currentYear,
        );

        $receipt = $this->signups->createBusiness($signup, $sizing, $listed);

        return [
            ...$receipt,
            'traction' => $this->signups->traction(),
            'business_preview' => $sizing,
        ];
    }
}

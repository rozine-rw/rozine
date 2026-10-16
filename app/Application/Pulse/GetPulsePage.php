<?php

declare(strict_types=1);

namespace App\Application\Pulse;

use App\Application\Pulse\Contracts\PulseSignupRepository;
use App\Domain\Pulse\PulseUnderwriting;

final class GetPulsePage
{
    public function __construct(
        private PulseSignupRepository $signups,
        private PreviewPulseInvestor $previewInvestor,
    ) {}

    /**
     * @param  array<string, list<string>>  $districts
     * @return array<string, mixed>
     */
    public function handle(array $districts, int $listingLimit, int $currentYear): array
    {
        return [
            'traction' => $this->signups->traction(),
            'districts' => $districts,
            'policy' => PulseUnderwriting::policy($currentYear),
            'investor_preview' => $this->previewInvestor->handle(
                PulseUnderwriting::PLEDGE_DEFAULT,
                $listingLimit,
            ),
        ];
    }
}

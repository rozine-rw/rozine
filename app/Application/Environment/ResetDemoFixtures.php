<?php

declare(strict_types=1);

namespace App\Application\Environment;

use App\Application\Environment\Contracts\DemoFixtureStore;

class ResetDemoFixtures
{
    public const string VERSION = 'pulse-foundation-v1';

    public const string PROVENANCE = 'rozine-synthetic:'.self::VERSION;

    public function __construct(
        private readonly EnvironmentIsolation $isolation,
        private readonly DemoFixtureStore $store,
    ) {}

    /**
     * Reset only the reserved, provenance-marked fixture set. Never wipe an
     * environment or invoke factories, notifications, credentials or providers.
     */
    public function handle(): int
    {
        $this->isolation->assertDemoResetAllowed();

        return $this->store->replace($this->fixtures(), self::PROVENANCE);
    }

    /**
     * Synthetic, authored inputs, not copied/anonymized production records or
     * approved underwriting vectors. No return, rating or loan is asserted.
     *
     * @return non-empty-list<array<string, string|int|bool|null>>
     */
    public function fixtures(): array
    {
        $common = [
            'contact_method' => 'email',
            'province' => 'Kigali City',
            'district' => 'Gasabo',
            'country' => 'Rwanda',
            'listed' => false,
            'pledge_amount' => null,
            'projected_return' => null,
            'blended_yield' => null,
            'annual_revenue' => null,
            'annual_costs' => null,
            'sector' => null,
            'registered_year' => null,
            'score' => null,
            'qualified_amount' => null,
            'term_months' => null,
            'flat_rate' => null,
            'rating_band' => null,
            'rating_score' => null,
            'loan_number' => null,
            'ip_address' => null,
            'user_agent' => self::PROVENANCE,
            'created_at' => '2026-09-14 00:00:00',
            'updated_at' => '2026-09-14 00:00:00',
        ];

        return [
            [...$common, 'type' => 'investor', 'name' => 'Synthetic demo investor',
                'contact' => 'investor@rozine-demo.invalid', 'queue_number' => 'DEMO-INV-0001',
                'pledge_amount' => 500000],
            [...$common, 'type' => 'business', 'name' => 'Synthetic demo business',
                'contact' => 'business@rozine-demo.invalid', 'queue_number' => 'DEMO-BIZ-0001'],
        ];
    }
}

<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Application\Operations\Contracts\CanonicalJson;
use App\Domain\Business\ApplicationDraft;
use App\Models\BusinessApplication;
use App\Models\BusinessApplicationQuote;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<BusinessApplicationQuote> */
class BusinessApplicationQuoteFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['id' => (string) Str::ulid(), 'business_application_id' => BusinessApplication::factory(), 'revision' => 1,
            'payload' => function (array $attributes): array {
                $application = BusinessApplication::query()->whereKey($attributes['business_application_id'])->firstOrFail();
                $now = now('UTC')->toImmutable();

                return ['quote_id' => $attributes['id'], 'quote_revision' => $attributes['revision'],
                    'application_id' => $application->id, 'application_revision' => $application->revision,
                    'business_id' => $application->business_id, 'mandate_version' => $application->mandate_version,
                    'draft' => (new ApplicationDraft)->normalize($application->draft), 'accepted_principal' => null, 'evidence' => null, 'credit' => null,
                    'credit_source_reference' => null, 'policy_version' => 'unsupported-fixture', 'calculation_version' => 'unsupported-fixture',
                    'calendar' => ['last_complete_month' => $now->startOfMonth()->subMonth()->format('Y-m'), 'first_repayment_month' => $now->format('Y-m')],
                    'evaluated_at' => $now->format('Y-m-d\TH:i:s\Z'), 'actor_user_id' => null, 'actor_party_id' => null,
                    'result' => ['eligible' => false, 'code' => 'POLICY_INPUT_REQUIRED']];
            },
            'sha256' => fn (array $attributes): string => hash('sha256', app(CanonicalJson::class)->encode($attributes['payload']))];
    }
}

<?php

declare(strict_types=1);

use App\Application\Business\Contracts\AcceptedApplicationStore;
use App\Domain\Operations\CommandRejection;
use App\Models\BusinessExposureReservation;
use Tests\Support\AuditSealingFixture;
use Tests\Support\BusinessCreditFactsFixture;

beforeEach(function (): void {
    $this->freezeSecond();
});

it('requires a published report before an accepted application can be released', function (bool $sealed): void {
    $fixture = AuditSealingFixture::ready();
    if ($sealed) {
        AuditSealingFixture::seal($fixture);
    }
    expect(fn () => app(AcceptedApplicationStore::class)->withReleaseInput($fixture['audit']['business'], $fixture['application']->id, fn (array $input): array => $input))
        ->toThrow(CommandRejection::class, 'REPORT_NOT_CURRENT');
})->with([false, true]);

it('validates the retained acceptance without counting its own exposure twice', function (): void {
    $fixture = AuditSealingFixture::ready(2);
    AuditSealingFixture::seal($fixture);
    AuditSealingFixture::cosign($fixture);
    AuditSealingFixture::cosign($fixture, 1, 2);
    $input = app(AcceptedApplicationStore::class)->withReleaseInput($fixture['audit']['business'], $fixture['application']->id, fn (array $input): array => $input);
    expect($input['principal'])->toBe('10800000')->and($input['reservation_id'])->toBe(BusinessExposureReservation::query()->sole()->id)
        ->and($input['report']['id'])->toBe($fixture['report']->id);
});

it('refuses a fresh restriction after the acceptance and report publication', function (): void {
    $fixture = AuditSealingFixture::ready();
    AuditSealingFixture::seal($fixture);
    AuditSealingFixture::cosign($fixture);
    $facts = BusinessCreditFactsFixture::facts();
    $facts['restriction_active'] = true;
    BusinessCreditFactsFixture::record($fixture['audit']['staff'], $fixture['audit']['business'], 1, facts: $facts);
    expect(fn () => app(AcceptedApplicationStore::class)->withReleaseInput($fixture['audit']['business'], $fixture['application']->id, fn (array $input): array => $input))
        ->toThrow(CommandRejection::class, 'QUOTE_STALE');
});

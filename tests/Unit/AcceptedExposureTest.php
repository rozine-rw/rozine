<?php

declare(strict_types=1);

use App\Domain\Underwriting\AcceptedExposure;
use App\Domain\Underwriting\ApplicationUnderwriting;
use App\Domain\Underwriting\CashFlowEvidence;
use App\Domain\Underwriting\EngineScorecard;
use App\Domain\Underwriting\FlatReturnPricing;
use App\Domain\Underwriting\LoanCapacity;
use App\Domain\Underwriting\UnderwritingViolation;
use Tests\Support\UnderwritingEvidenceFixture;

it('counts each accepted economic commitment once across evidence sources', function (): void {
    $obligations = [['id' => 'already-reported', 'principal' => '3000000', 'service_by_month' => []]];
    $commitments = [['id' => 'already-reported', 'principal' => '3000000'], ['id' => 'new', 'principal' => '5000000'], ['id' => 'new', 'principal' => '5000000']];
    expect((string) (new AcceptedExposure)->additional($obligations, $commitments))->toBe('5000000')
        ->and((string) (new AcceptedExposure)->additional([], []))->toBe('0');
});

it('rejects conflicting or malformed accepted exposure', function (string $id, string $principal, string $reason): void {
    expect(fn () => (new AcceptedExposure)->additional([['id' => 'prior', 'principal' => '3000000', 'service_by_month' => []]], [['id' => $id, 'principal' => $principal]]))
        ->toThrow(UnderwritingViolation::class, $reason);
})->with([
    ['', '3000000', 'OBLIGATION_EVIDENCE_CONFLICT'],
    ['prior', '5000000', 'OBLIGATION_EVIDENCE_CONFLICT'],
    ['new', '-1', 'INVALID_MONEY'],
]);

it('reduces exact borrowing room without inventing debt service for an unissued commitment', function (string $principal, string $room): void {
    $calculator = new ApplicationUnderwriting(new CashFlowEvidence, new EngineScorecard, new FlatReturnPricing, new LoanCapacity);
    $inputs = [...UnderwritingEvidenceFixture::application(), 'accepted_commitments' => [['id' => 'accepted', 'principal' => $principal]]];
    $result = $calculator->evaluate($inputs);
    expect($result['cash_flow']['remaining_room'])->toBe(['numerator' => $room, 'denominator' => '1'])
        ->and($result['cash_flow']['existing_debt_service'])->toBe(['numerator' => '0', 'denominator' => '1'])
        ->and($calculator->evaluate($result['inputs']))->toBe($result);
})->with([['10000000', '6800000'], ['20000000', '-3200000']]);

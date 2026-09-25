<?php

declare(strict_types=1);

use App\Domain\Auditor\AuditReportWindow;
use App\Domain\Operations\CommandRejection;

it('uses the complete by-the-seventh Kigali calendar window without inventing a Flash deadline', function (): void {
    $window = new AuditReportWindow;
    expect($window->dueAt(null))->toBeNull()->and($window->mayPublish(null, new DateTimeImmutable('2027-01-01T00:00:00Z')))->toBeTrue()
        ->and($window->dueAt('2026-08'))->toBe('2026-09-07T21:59:59Z')
        ->and($window->dueAt('2026-12'))->toBe('2027-01-07T21:59:59Z');
});

it('enforces opening and closing instants in Kigali including fractional final seconds', function (string $instant, bool $allowed): void {
    expect((new AuditReportWindow)->mayPublish('2026-08', new DateTimeImmutable($instant)))->toBe($allowed);
})->with([
    ['2026-08-31T21:59:59.999999Z', false], ['2026-08-31T22:00:00Z', true],
    ['2026-09-07T21:59:59.999999Z', true], ['2026-09-07T22:00:00Z', false],
]);

it('refuses invalid reporting cycles', function (string $period): void {
    expect(fn () => (new AuditReportWindow)->dueAt($period))->toThrow(CommandRejection::class, 'AUDIT_PERIOD_INVALID');
})->with(['2026-13', '2026-0', 'invalid']);

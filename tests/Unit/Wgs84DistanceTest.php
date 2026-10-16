<?php

declare(strict_types=1);

use App\Domain\Auditor\AuditorDispatch;
use App\Domain\Auditor\AuditorStanding;
use App\Domain\Auditor\Wgs84Distance;
use App\Domain\Operations\CommandRejection;
use Tests\Support\AuditorFixture;

/** Independent WGS84 reference distances: geographiclib/geographiclib-python, test/test_geodesic.py. */
it('bounds independent inverse geodesic vectors in both directions', function (string $latitude, string $longitude, string $otherLatitude, string $otherLongitude, float $reference): void {
    $geodesic = new Wgs84Distance;
    $from = $geodesic->point($latitude, $longitude);
    $to = $geodesic->point($otherLatitude, $otherLongitude);
    $bound = $geodesic->upperBound($from, $to);

    expect($bound)->toBe((int) ceil($reference + 0.01))
        ->toBeGreaterThan($reference)->toBeLessThan($reference + 1.02)
        ->and($geodesic->upperBound($to, $from))->toBe($bound);
})->with([
    ['35.60777', '-139.44815', '-11.17491', '-69.95921', 8935244.5604818305],
    ['-21.97856', '142.59065', '41.84138', '98.56635', 8394328.894657671],
    ['-50.56724', '-16.30485', '-33.56571', '-94.97412', 6455670.5118668696],
    ['-87.85331', '85.66836', '66.48646', '16.09921', 17286615.3147144645],
    ['-41.22777', '122.32875', '-7.57291', '130.37946', 3812686.035106021],
]);

it('handles meridians the equator and short antimeridian crossings conservatively', function (): void {
    $geodesic = new Wgs84Distance;
    $origin = $geodesic->point('0', '0');
    expect($geodesic->upperBound($origin, $geodesic->point('1', '0')))->toBe(110575)
        ->and($geodesic->upperBound($origin, $geodesic->point('0', '1')))->toBe(111320)
        ->and($geodesic->upperBound($geodesic->point('0', '179'), $geodesic->point('0', '-179')))->toBe(222639)
        ->and($geodesic->upperBound($origin, $geodesic->point('0', '0.0000001')))->toBe(1);
});

it('recognizes coincident coordinates and equivalent pole or antimeridian positions', function (string $latitude, string $longitude, string $otherLongitude): void {
    $geodesic = new Wgs84Distance;
    expect($geodesic->upperBound($geodesic->point($latitude, $longitude), $geodesic->point($latitude, $otherLongitude)))->toBe(0);
})->with([['-1.9441', '30.0619', '30.0619'], ['0', '180', '-180'], ['90', '1', '-170'], ['-90', '-30', '125']]);

it('leaves nonconvergent antipodal distance unknown and ineligible', function (): void {
    $geodesic = new Wgs84Distance;
    $candidate = AuditorFixture::candidate();
    $candidate['distance_upper_bound_m'] = $geodesic->upperBound($geodesic->point('0', '0'), $geodesic->point('0', '180'));
    expect($candidate['distance_upper_bound_m'])->toBeNull()
        ->and((new AuditorDispatch(new AuditorStanding))->reasons($candidate, new DateTimeImmutable('2026-09-24T08:00:00Z')))->toBe(['AUDITOR_OUTSIDE_RADIUS']);
});

it('adds both measured uncertainty radii to the computed distance at admission', function (): void {
    $geodesic = new Wgs84Distance;
    $candidate = AuditorFixture::candidate();
    $candidate['distance_upper_bound_m'] = $geodesic->upperBound($geodesic->point('0', '0'), $geodesic->point('0', '0.26'));
    $candidate['office']['uncertainty_m'] = 500;
    $candidate['premises']['uncertainty_m'] = 556;
    $policy = new AuditorDispatch(new AuditorStanding);
    $now = new DateTimeImmutable('2026-09-24T08:00:00Z');
    expect($candidate['distance_upper_bound_m'])->toBe(28944)->and($policy->reasons($candidate, $now))->toBe([]);
    $candidate['premises']['uncertainty_m'] = 557;
    expect($policy->reasons($candidate, $now))->toBe(['AUDITOR_OUTSIDE_RADIUS']);
});

it('rejects malformed out of range or excessive precision coordinates instead of coercing them', function (string $latitude, string $longitude): void {
    $geodesic = new Wgs84Distance;
    expect(fn () => $geodesic->point($latitude, $longitude))->toThrow(CommandRejection::class, 'AUDIT_COORDINATES_INVALID')
        ->and(fn () => $geodesic->upperBound(['latitude' => $latitude, 'longitude' => $longitude], $geodesic->point('0', '0')))->toThrow(CommandRejection::class, 'AUDIT_COORDINATES_INVALID');
})->with([
    ['91', '0'], ['-90.0000001', '0'], ['0', '180.0000001'], ['0', '-181'],
    ['NaN', '0'], ['0', 'INF'], ['1e1', '0'], ['+1', '0'], [' 1', '0'],
    ['0', "1\n"], ['01', '0'], ['0', '30.12345678'], ['', '0'],
]);

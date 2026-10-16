<?php

declare(strict_types=1);

namespace App\Domain\Auditor;

use App\Domain\Operations\CommandRejection;

/**
 * WGS84 inverse geodesic, Vincenty (1975), equations 3-6 and 10-20:
 * https://www.ngs.noaa.gov/PUBS_LIB/inverse.pdf
 *
 * Uses a 1 cm numerical allowance and rounds upward to whole metres for admission.
 * Non-convergent pairs are unknown, never replaced by an eligibility-granting approximation.
 * Coordinate measurement uncertainty is added separately by AuditorDispatch.
 *
 * @phpstan-type Point array{latitude: string, longitude: string}
 */
final class Wgs84Distance
{
    private const float MAJOR_AXIS = 6378137.0;

    private const float FLATTENING = 1.0 / 298.257223563;

    /** @return Point */
    public function point(string $latitude, string $longitude): array
    {
        foreach ([$latitude, $longitude] as $value) {
            if (! preg_match('/^-?(?:0|[1-9][0-9]{0,2})(?:\.[0-9]{1,7})?$/D', $value)) {
                throw new CommandRejection('AUDIT_COORDINATES_INVALID', 422);
            }
        }
        if (abs((float) $latitude) > 90 || abs((float) $longitude) > 180) {
            throw new CommandRejection('AUDIT_COORDINATES_INVALID', 422);
        }

        return ['latitude' => $latitude, 'longitude' => $longitude];
    }

    /**
     * @param  Point  $from
     * @param  Point  $to
     */
    public function upperBound(array $from, array $to): ?int
    {
        $from = $this->point($from['latitude'], $from['longitude']);
        $to = $this->point($to['latitude'], $to['longitude']);
        $latitudeFrom = (float) $from['latitude'];
        $latitudeTo = (float) $to['latitude'];
        if ($latitudeFrom === $latitudeTo && abs($latitudeFrom) === 90.0) {
            return 0;
        }
        $reducedFrom = atan((1 - self::FLATTENING) * tan(deg2rad($latitudeFrom)));
        $reducedTo = atan((1 - self::FLATTENING) * tan(deg2rad($latitudeTo)));
        $sinFrom = sin($reducedFrom);
        $cosFrom = cos($reducedFrom);
        $sinTo = sin($reducedTo);
        $cosTo = cos($reducedTo);
        $longitude = deg2rad(fmod((float) $to['longitude'] - (float) $from['longitude'] + 540, 360) - 180);
        $lambda = $longitude;
        for ($iteration = 0; $iteration < 200; $iteration++) {
            $sinLambda = sin($lambda);
            $cosLambda = cos($lambda);
            $sinSigma = hypot($cosTo * $sinLambda, $cosFrom * $sinTo - $sinFrom * $cosTo * $cosLambda);
            if ($sinSigma === 0.0) {
                return 0;
            }
            $cosSigma = $sinFrom * $sinTo + $cosFrom * $cosTo * $cosLambda;
            $sigma = atan2($sinSigma, $cosSigma);
            $sinAlpha = max(-1.0, min(1.0, $cosFrom * $cosTo * $sinLambda / $sinSigma));
            $cosSquaredAlpha = 1 - $sinAlpha * $sinAlpha;
            $cosDoubleMidpoint = $cosSquaredAlpha < 1e-24 ? 0.0 : $cosSigma - 2 * $sinFrom * $sinTo / $cosSquaredAlpha;
            $coefficient = self::FLATTENING / 16 * $cosSquaredAlpha * (4 + self::FLATTENING * (4 - 3 * $cosSquaredAlpha));
            $previous = $lambda;
            $lambda = $longitude + (1 - $coefficient) * self::FLATTENING * $sinAlpha
                * ($sigma + $coefficient * $sinSigma * ($cosDoubleMidpoint + $coefficient * $cosSigma * (-1 + 2 * $cosDoubleMidpoint ** 2)));
            if (abs($lambda - $previous) <= 1e-12) {
                $minorAxis = self::MAJOR_AXIS * (1 - self::FLATTENING);
                $squared = $cosSquaredAlpha * (self::MAJOR_AXIS ** 2 - $minorAxis ** 2) / $minorAxis ** 2;
                $seriesA = 1 + $squared / 16384 * (4096 + $squared * (-768 + $squared * (320 - 175 * $squared)));
                $seriesB = $squared / 1024 * (256 + $squared * (-128 + $squared * (74 - 47 * $squared)));
                $correction = $seriesB * $sinSigma * ($cosDoubleMidpoint + $seriesB / 4
                    * ($cosSigma * (-1 + 2 * $cosDoubleMidpoint ** 2) - $seriesB / 6 * $cosDoubleMidpoint
                        * (-3 + 4 * $sinSigma ** 2) * (-3 + 4 * $cosDoubleMidpoint ** 2)));

                return (int) ceil($minorAxis * $seriesA * ($sigma - $correction) + 0.01);
            }
        }

        return null;
    }
}

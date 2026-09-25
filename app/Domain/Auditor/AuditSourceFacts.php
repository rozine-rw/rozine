<?php

declare(strict_types=1);

namespace App\Domain\Auditor;

use App\Domain\Operations\CommandRejection;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Isolated synthetic procedure inputs for C2 acceptance: a Business stock declaration and a
 * capture-package summary. Every fact is explicitly nullable; an absent fact stays null and is
 * never replaced by zero. Photo metadata carries no image location or URL.
 *
 * @phpstan-type Position array{latitude: string, longitude: string, accuracy_m: int|null}
 * @phpstan-type Photo array{id: string, captured_at: string|null, position: Position|null, title: string|null}
 * @phpstan-type Facts array{declared_stock_rwf: string|null, declared_stock_units: string|null, declared_unit_label: string|null, declared_sector_label: string|null, declared_account_label: string|null, check_in: array{at: string|null, position: Position|null, review_required: bool|null}, photos: array{required: list<Photo>|null, extra: list<Photo>|null}, proof_ids: array{financial: list<string>|null, inventory: list<string>|null}}
 */
final class AuditSourceFacts
{
    public const SOURCE_KIND = 'isolated_synthetic';

    public function __construct(private Wgs84Distance $geodesic) {}

    /**
     * @param  array<string, mixed>  $input
     * @return Facts
     */
    public function normalize(array $input, DateTimeImmutable $now): array
    {
        $this->keys($input, ['declared_stock_rwf', 'declared_stock_units', 'declared_unit_label', 'declared_sector_label',
            'declared_account_label', 'check_in', 'photos', 'proof_ids']);
        $checkIn = $this->map($input['check_in'], ['at', 'position', 'review_required']);
        $photos = $this->map($input['photos'], ['required', 'extra']);
        $proofs = $this->map($input['proof_ids'], ['financial', 'inventory']);
        if ($checkIn['review_required'] !== null && ! is_bool($checkIn['review_required'])) {
            $this->invalid();
        }
        $required = $this->photos($photos['required'], $now);
        $extra = $this->photos($photos['extra'], $now);
        $photoIds = array_column([...$required ?? [], ...$extra ?? []], 'id');
        if (count($photoIds) !== count(array_unique($photoIds))) {
            $this->invalid();
        }

        return ['declared_stock_rwf' => $this->wholeNumber($input['declared_stock_rwf'], 64),
            'declared_stock_units' => $this->wholeNumber($input['declared_stock_units'], 18),
            'declared_unit_label' => $this->label($input['declared_unit_label'], 120),
            'declared_sector_label' => $this->label($input['declared_sector_label'], 120),
            'declared_account_label' => $this->label($input['declared_account_label'], 120),
            'check_in' => ['at' => $this->timestamp($checkIn['at'], $now), 'position' => $this->position($checkIn['position']),
                'review_required' => $checkIn['review_required']],
            'photos' => ['required' => $required, 'extra' => $extra],
            'proof_ids' => ['financial' => $this->identifiers($proofs['financial']), 'inventory' => $this->identifiers($proofs['inventory'])]];
    }

    /** @return list<Photo>|null */
    private function photos(mixed $input, DateTimeImmutable $now): ?array
    {
        if ($input === null) {
            return null;
        }
        if (! is_array($input) || ! array_is_list($input) || count($input) > 20) {
            $this->invalid();
        }
        $photos = [];
        foreach ($input as $photo) {
            $photo = $this->map($photo, ['id', 'captured_at', 'position', 'title']);
            $photos[] = ['id' => $this->identifier($photo['id']), 'captured_at' => $this->timestamp($photo['captured_at'], $now),
                'position' => $this->position($photo['position']), 'title' => $this->label($photo['title'], 50)];
        }

        return $photos;
    }

    /** @return list<string>|null */
    private function identifiers(mixed $input): ?array
    {
        if ($input === null) {
            return null;
        }
        if (! is_array($input) || ! array_is_list($input) || count($input) > 20) {
            $this->invalid();
        }
        $identifiers = array_map(fn (mixed $identifier): string => $this->identifier($identifier), $input);
        if (count($identifiers) !== count(array_unique($identifiers))) {
            $this->invalid();
        }

        return $identifiers;
    }

    private function identifier(mixed $input): string
    {
        if (! is_string($input) || preg_match('/^[a-z0-9][a-z0-9._-]{0,79}$/D', $input) !== 1) {
            $this->invalid();
        }

        return $input;
    }

    /** @return Position|null */
    private function position(mixed $input): ?array
    {
        if ($input === null) {
            return null;
        }
        $position = $this->map($input, ['latitude', 'longitude', 'accuracy_m']);
        if (! is_string($position['latitude']) || ! is_string($position['longitude'])
            || ($position['accuracy_m'] !== null && (! is_int($position['accuracy_m']) || $position['accuracy_m'] < 0 || $position['accuracy_m'] > 30000))) {
            $this->invalid();
        }
        try {
            $point = $this->geodesic->point($position['latitude'], $position['longitude']);
        } catch (CommandRejection) {
            $this->invalid();
        }

        return [...$point, 'accuracy_m' => $position['accuracy_m']];
    }

    private function wholeNumber(mixed $input, int $digits): ?string
    {
        if ($input === null) {
            return null;
        }
        if (! is_string($input) || preg_match('/^(0|[1-9][0-9]{0,'.($digits - 1).'})$/D', $input) !== 1) {
            $this->invalid();
        }

        return $input;
    }

    private function label(mixed $input, int $limit): ?string
    {
        if ($input === null) {
            return null;
        }
        if (! is_string($input) || ! mb_check_encoding($input, 'UTF-8') || trim($input) === '' || mb_strlen($input) > $limit
            || preg_match('/[\p{Cc}\p{Cf}]/u', $input)) {
            $this->invalid();
        }

        return $input;
    }

    private function timestamp(mixed $input, DateTimeImmutable $now): ?string
    {
        if ($input === null) {
            return null;
        }
        $date = is_string($input) ? DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s\Z', $input, new DateTimeZone('UTC')) : false;
        if ($date === false || $date->format('Y-m-d\TH:i:s\Z') !== $input || $date > $now) {
            $this->invalid();
        }

        return $input;
    }

    /**
     * @param  list<string>  $expected
     * @return array<string, mixed>
     */
    private function map(mixed $input, array $expected): array
    {
        if (! is_array($input)) {
            $this->invalid();
        }
        $this->keys($input, $expected);

        /** @var array<string, mixed> $input */
        return $input;
    }

    /**
     * @param  array<array-key, mixed>  $input
     * @param  list<string>  $expected
     */
    private function keys(array $input, array $expected): void
    {
        $keys = array_keys($input);
        sort($keys);
        sort($expected);
        if ($keys !== $expected) {
            $this->invalid();
        }
    }

    private function invalid(): never
    {
        throw new CommandRejection('AUDIT_SOURCE_FACTS_INVALID', 422);
    }
}

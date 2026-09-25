<?php

declare(strict_types=1);

namespace App\Infrastructure\Operations;

use App\Application\Operations\Contracts\CanonicalJson;
use App\Domain\Operations\CommandRejection;
use Mmccook\JsonCanonicalizator\JsonCanonicalizatorFactory;

/** Whole money and exact ratios are strings; JSON numbers are limited to exact small integers. */
final class JcsCanonicalJson implements CanonicalJson
{
    /** @param array<string, mixed> $value */
    public function encode(array $value): string
    {
        $this->validate($value);

        return JsonCanonicalizatorFactory::getInstance()->canonicalize((object) $value, false);
    }

    private function validate(mixed $value): void
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $this->validate($key);
                $this->validate($item);
            }

            return;
        }
        if ($value === null || is_bool($value) || (is_int($value) && $value >= -9007199254740991 && $value <= 9007199254740991)) {
            return;
        }
        if (is_string($value) && mb_check_encoding($value, 'UTF-8') && ! preg_match('/[\x{2028}\x{2029}]/u', $value)) {
            return;
        }

        throw new CommandRejection('CANONICAL_VALUE_INVALID', 422);
    }
}

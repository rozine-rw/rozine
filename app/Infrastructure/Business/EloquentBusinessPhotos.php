<?php

declare(strict_types=1);

namespace App\Infrastructure\Business;

use App\Application\Business\Contracts\BusinessPhotos;
use App\Domain\Operations\CommandRejection;
use App\Models\BusinessCampaign;
use App\Models\BusinessPhoto;
use App\Models\BusinessProfile;
use InvalidArgumentException;

/** Business photos as `business_photos` rows: base64 bytes pinned by the SHA-256 of the raw file. */
final class EloquentBusinessPhotos implements BusinessPhotos
{
    public function published(string $businessId): array
    {
        return array_values(BusinessPhoto::query()->where('business_id', $businessId)->orderBy('position')->get(['id', 'caption', 'sha256'])
            ->map(fn (BusinessPhoto $photo): array => ['id' => $photo->id, 'caption' => $photo->caption, 'sha256' => $photo->sha256])->all());
    }

    public function file(string $campaignId, string $photoId): array
    {
        $businessId = BusinessCampaign::query()->whereKey($campaignId)->value('business_id');
        $photo = is_string($businessId) ? BusinessPhoto::query()->where('business_id', $businessId)->find($photoId) : null;
        if ($photo === null) {
            throw new CommandRejection('BUSINESS_PHOTO_NOT_FOUND', 404);
        }
        $bytes = base64_decode($photo->content, true);
        if ($bytes === false || ! hash_equals($photo->sha256, hash('sha256', $bytes))) {
            throw new CommandRejection('BUSINESS_PHOTO_INTEGRITY_FAILED', 409);
        }

        return ['media_type' => $photo->media_type, 'sha256' => $photo->sha256, 'bytes' => $bytes];
    }

    public function put(string $businessId, int $position, string $caption, string $mediaType, string $bytes): array
    {
        $caption = trim($caption);
        if (! BusinessProfile::query()->whereKey($businessId)->exists()) {
            throw new InvalidArgumentException('BUSINESS_PHOTO_BUSINESS_UNKNOWN');
        }
        if ($position < 1 || $position > self::MAX_PHOTOS || $caption === '' || mb_strlen($caption) > 80
            || ! in_array($mediaType, self::MEDIA_TYPES, true) || $bytes === '' || strlen($bytes) > self::MAX_BYTES
            || ! self::isOfType($bytes, $mediaType)) {
            throw new InvalidArgumentException('BUSINESS_PHOTO_INVALID');
        }
        $sha256 = hash('sha256', $bytes);
        $current = BusinessPhoto::query()->where('business_id', $businessId)->where('position', $position)->first();
        if ($current !== null && $current->sha256 === $sha256 && $current->caption === $caption) {
            return ['id' => $current->id, 'changed' => false];
        }
        $photo = $current ?? new BusinessPhoto;
        $photo->forceFill(['business_id' => $businessId, 'position' => $position, 'caption' => $caption, 'media_type' => $mediaType,
            'size_bytes' => strlen($bytes), 'sha256' => $sha256, 'content' => base64_encode($bytes)])->save();

        return ['id' => $photo->id, 'changed' => true];
    }

    /** Whether the bytes open with the signature of the named image type, so a label cannot dress up another file. */
    private static function isOfType(string $bytes, string $mediaType): bool
    {
        return match ($mediaType) {
            'image/jpeg' => str_starts_with($bytes, "\xFF\xD8\xFF"),
            'image/png' => str_starts_with($bytes, "\x89PNG\r\n\x1A\n"),
            default => str_starts_with($bytes, 'RIFF') && substr($bytes, 8, 4) === 'WEBP',
        };
    }
}

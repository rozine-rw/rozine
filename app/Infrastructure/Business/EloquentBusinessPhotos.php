<?php

declare(strict_types=1);

namespace App\Infrastructure\Business;

use App\Application\Business\Contracts\BusinessPhotos;
use App\Domain\Business\PhotoBytes;
use App\Domain\Operations\CommandRejection;
use App\Models\BusinessCampaign;
use App\Models\BusinessCampaignClosure;
use App\Models\BusinessPhoto;
use App\Models\BusinessProfile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Business photos as `business_photos` rows pinned by the SHA-256 of the raw file: the file on the
 * public media disk under an unguessable `{business}/{ulid}.{ext}` name when one is configured, or
 * base64 in the row.
 */
final class EloquentBusinessPhotos implements BusinessPhotos
{
    public function published(string $businessId): array
    {
        return array_values(BusinessPhoto::query()->where('business_id', $businessId)->orderBy('position')->get(['id', 'caption', 'sha256', 'url'])
            ->map(fn (BusinessPhoto $photo): array => ['id' => $photo->id, 'caption' => $photo->caption, 'sha256' => $photo->sha256, 'url' => $photo->url])->all());
    }

    public function file(string $campaignId, string $photoId): array
    {
        $businessId = BusinessCampaign::query()->whereKey($campaignId)
            ->whereNotIn('id', BusinessCampaignClosure::query()->select('business_campaign_id'))->value('business_id');
        $photo = is_string($businessId) ? BusinessPhoto::query()->where('business_id', $businessId)->find($photoId) : null;
        if ($photo?->content === null) {
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
            || ! PhotoBytes::whole($bytes, $mediaType)) {
            throw new InvalidArgumentException('BUSINESS_PHOTO_INVALID');
        }
        $sha256 = hash('sha256', $bytes);
        $current = BusinessPhoto::query()->where('business_id', $businessId)->where('position', $position)->first();
        if ($current !== null && $current->sha256 === $sha256 && $current->caption === $caption) {
            return ['id' => $current->id, 'changed' => false];
        }
        $url = self::publish($businessId, $mediaType, $bytes);
        $photo = $current ?? new BusinessPhoto;
        $photo->forceFill(['business_id' => $businessId, 'position' => $position, 'caption' => $caption, 'media_type' => $mediaType,
            'size_bytes' => strlen($bytes), 'sha256' => $sha256, 'content' => $url === null ? base64_encode($bytes) : null, 'url' => $url])->save();

        return ['id' => $photo->id, 'changed' => true];
    }

    public function relocate(bool $dryRun = false): int
    {
        if (! is_string(config('filesystems.media_disk'))) {
            return 0;
        }
        $moved = 0;
        BusinessPhoto::query()->whereNotNull('content')->orderBy('id')->each(function (BusinessPhoto $photo) use ($dryRun, &$moved): void {
            $bytes = base64_decode((string) $photo->content, true);
            if ($bytes === false || ! hash_equals($photo->sha256, hash('sha256', $bytes))) {
                throw new CommandRejection('BUSINESS_PHOTO_INTEGRITY_FAILED', 409);
            }
            if (! $dryRun) {
                $photo->forceFill(['url' => self::publish($photo->business_id, $photo->media_type, $bytes), 'content' => null])->save();
            }
            $moved++;
        });

        return $moved;
    }

    /** Puts the bytes on the public media disk and returns their URL, or null when there is no media disk. */
    private static function publish(string $businessId, string $mediaType, string $bytes): ?string
    {
        $disk = config('filesystems.media_disk');
        if (! is_string($disk)) {
            return null;
        }
        $path = $businessId.'/'.strtolower((string) Str::ulid()).'.'.['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mediaType];
        Storage::disk($disk)->put($path, $bytes, ['visibility' => 'public', 'ContentType' => $mediaType]);

        return Storage::disk($disk)->url($path);
    }
}

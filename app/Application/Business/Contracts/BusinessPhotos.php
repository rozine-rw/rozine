<?php

declare(strict_types=1);

namespace App\Application\Business\Contracts;

/**
 * The captioned photos a Business publishes for Investors: the deal card banner and the deal's
 * Photos section. Each photo is a position from 1 to 6 with a caption and one JPEG, PNG or WebP
 * file of at most 5 MB, retained in the database and pinned by its SHA-256.
 *
 * @phpstan-type PublishedPhoto array{id: string, caption: string, sha256: string}
 */
interface BusinessPhotos
{
    public const int MAX_PHOTOS = 6;

    public const int MAX_BYTES = 5_242_880;

    public const array MEDIA_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    /**
     * The Business's photos in position order.
     *
     * @return list<PublishedPhoto>
     */
    public function published(string $businessId): array;

    /**
     * One photo of the Business behind an open campaign; rejected with 404 when the campaign is
     * closed or the photo is not that Business's, and with 409 when its bytes no longer match their pinned digest.
     *
     * @return array{media_type: string, sha256: string, bytes: string}
     */
    public function file(string $campaignId, string $photoId): array;

    /**
     * Puts a photo at a position, replacing whatever was there. Putting the same caption and bytes
     * again changes nothing, so a seed can run twice.
     *
     * @return array{id: string, changed: bool}
     */
    public function put(string $businessId, int $position, string $caption, string $mediaType, string $bytes): array;
}

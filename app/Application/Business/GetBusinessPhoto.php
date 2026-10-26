<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Business\Contracts\BusinessPhotos;

/**
 * One photo a Business published, read through the campaign an Investor is looking at. Photos are
 * the Business's own public material for Investors, so any signed-in person may load them; the
 * campaign only names whose photos to look in, and a photo of another Business is not found.
 */
final class GetBusinessPhoto
{
    public function __construct(private BusinessPhotos $photos) {}

    /** @return array{media_type: string, sha256: string, bytes: string} */
    public function handle(string $campaignId, string $photoId): array
    {
        return $this->photos->file($campaignId, $photoId);
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Business\Contracts\BusinessPhotos;
use App\Application\Primary\GetInvestorViewer;

/**
 * One photo a Business published, read through the deal an Investor is looking at. Photos follow
 * the Deals deck: only someone who may read the deck loads them, and only while the deal is open.
 * The campaign names whose photos to look in, so a photo of another Business is not found.
 */
final class GetBusinessPhoto
{
    public function __construct(private GetInvestorViewer $viewer, private BusinessPhotos $photos) {}

    /** @return array{media_type: string, sha256: string, bytes: string} */
    public function handle(int $userId, string $campaignId, string $photoId): array
    {
        $this->viewer->admit($userId);

        return $this->photos->file($campaignId, $photoId);
    }
}

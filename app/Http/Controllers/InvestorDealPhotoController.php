<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Business\GetBusinessPhoto;
use Symfony\Component\HttpFoundation\Response;

/** One published Business photo for the deal card and the deal's Photos section (`investor.deals.photo`). */
class InvestorDealPhotoController extends Controller
{
    public function __invoke(GetBusinessPhoto $action, string $campaign, string $photo): Response
    {
        $file = $action->handle($campaign, $photo);

        // The URL names the bytes' digest, so a browser may keep them for a day.
        return response($file['bytes'], 200, [
            'Content-Type' => $file['media_type'],
            'ETag' => '"'.$file['sha256'].'"',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
            'Cache-Control' => 'private, max-age=86400, immutable',
        ]);
    }
}

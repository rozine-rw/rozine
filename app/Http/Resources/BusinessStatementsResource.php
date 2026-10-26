<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The Business's own bank and Mobile Money statements (BRS APP-B01, plan C-14): what has been
 * received, how far reading each one has got, and where the next one is uploaded. Receiving a
 * statement verifies nothing; an Audit Partner verifies the figures before any capacity exists.
 *
 * @phpstan-import-type Manifest from \App\Application\Evidence\Contracts\StatementStore
 */
class BusinessStatementsResource extends JsonResource
{
    /** The reading stage of each extraction status; anything unknown is shown as needing review. */
    private const READING = ['pending' => 'reading', 'text_extracted' => 'read', 'needs_review' => 'needs_review'];

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array{business_id: string, manifest: Manifest} $data */
        $data = $this->resource;
        $prefix = $request->routeIs('api.*') ? 'api.v1.business.statements.' : 'business.statements.';
        $business = $data['business_id'];

        return ['revision' => $data['manifest']['revision'],
            'documents' => array_map(fn (array $document): array => ['id' => $document['id'], 'filename' => $document['filename'],
                'media_type' => $document['media_type'], 'size_bytes' => $document['size_bytes'], 'received_at' => $document['received_at'],
                'reading' => self::READING[$document['extraction']['status']] ?? 'needs_review',
                'link' => ['url' => route($prefix.'show', ['business' => $business, 'document' => $document['id']], false), 'method' => 'get']],
                $data['manifest']['documents']),
            'upload' => ['url' => route($prefix.'upload', ['business' => $business], false), 'method' => 'post']];
    }
}

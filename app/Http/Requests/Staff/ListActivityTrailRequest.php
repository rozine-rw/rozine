<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Pagination\Cursor;
use Illuminate\Support\Str;

/** The trail's search, its preset or date range, and how many entries to show. */
class ListActivityTrailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*') || $this->user()->tokenCan('staff:events:read'));
    }

    /** @return array<string, list<string|\Closure>> */
    public function rules(): array
    {
        return ['q' => ['sometimes', 'nullable', 'string', 'max:120'], 'preset' => ['sometimes', 'nullable', 'string', 'in:today,7d,30d'],
            'from' => ['sometimes', 'nullable', 'date_format:Y-m-d'], 'to' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:500'],
            'cursor' => ['sometimes', 'nullable', 'string', 'max:512', function (string $attribute, mixed $value, \Closure $fail): void {
                $cursor = is_string($value) ? Cursor::fromEncoded($value) : null;
                $parts = $cursor?->toArray() ?? [];
                if (($parts['_pointsToNextItems'] ?? null) !== true
                    || ! is_string($parts['created_at'] ?? null) || strtotime($parts['created_at']) === false
                    || ! is_string($parts['id'] ?? null) || ! Str::isUlid($parts['id'])
                    || ! in_array($parts['journal'] ?? null, ['identity', 'operations'], true)) {
                    $fail('The activity cursor is invalid.');
                }
            }]];
    }
}
